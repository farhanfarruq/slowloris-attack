<?php

namespace App\Services;

use App\Models\AiResult;
use App\Models\Experiment;

class AnalysisComparisonService
{
    public function __construct(private ?ToolProfileService $profiles = null)
    {
        $this->profiles ??= new ToolProfileService();
    }

    public function summarize(Experiment $experiment, ?AiResult $aiResult = null): array
    {
        $experiment->loadMissing(['extractedFeature', 'aiResults']);
        $latestAi = $aiResult ?: $experiment->aiResults()->where('is_simulated', false)->latest()->first();
        $features = $experiment->extractedFeature;
        $toolProfile = $this->profiles->normalize($experiment->tool_profile ?? $latestAi?->tool_profile);
        $logicClassification = (string) ($features?->attack_category ?? $latestAi?->logic_classification ?? 'Inconclusive');
        $logicScore = (float) ($features?->final_attack_score ?? $latestAi?->logic_score ?? 0);
        $aiClassification = (string) ($latestAi?->classification ?? 'Inconclusive');
        $aiConfidence = (float) ($latestAi?->confidence_score ?? 0);
        $gateReasons = $this->gateReasons($features?->raw_features, $latestAi?->logic_gate_reasons);

        $agreement = $this->agreement($toolProfile, $logicClassification, $logicScore, $aiClassification, $gateReasons);

        return [
            'tool_profile' => $toolProfile,
            'tool_label' => $this->profiles->get($toolProfile)['label'] ?? $toolProfile,
            'attack_pattern' => $experiment->attack_pattern ?? $experiment->scenario_key,
            'logic_classification' => $logicClassification,
            'logic_score' => round($logicScore, 2),
            'ai_classification' => $aiClassification,
            'ai_confidence' => round($aiConfidence, 2),
            'agreement' => $agreement,
            'gate_reasons' => $gateReasons,
            'missing_evidence' => $latestAi?->missing_evidence ?? [],
            'recommendation' => $latestAi?->recommendation,
            'chart_data' => $this->chartData($logicScore, $aiConfidence, $agreement, $latestAi?->ai_chart_data),
        ];
    }

    public function forExperiment(Experiment $experiment): array
    {
        $experiment->loadMissing('aiResults');

        if ($experiment->aiResults->isEmpty()) {
            return [$this->summarize($experiment)];
        }

        return $experiment->aiResults
            ->sortByDesc('created_at')
            ->take(8)
            ->map(fn (AiResult $result) => $this->summarize($experiment, $result))
            ->values()
            ->all();
    }

    private function agreement(string $toolProfile, string $logicClassification, float $logicScore, string $aiClassification, array $gateReasons): string
    {
        $detectedLabel = $this->profiles->detectedLabel($toolProfile);
        $logicDetected = str_contains(strtolower($logicClassification), strtolower($this->profiles->get($toolProfile)['label'] ?? $toolProfile))
            && $logicScore > 75;
        $aiDetected = $aiClassification === $detectedLabel;

        if ($aiDetected && $gateReasons !== []) {
            return 'blocked_by_evidence_gate';
        }

        if ($logicClassification === $aiClassification || ($logicDetected && $aiDetected)) {
            return 'match';
        }

        if (($logicScore >= 56 && in_array($aiClassification, ['Suspicious', 'Inconclusive'], true))
            || (!$logicDetected && !$aiDetected && $aiClassification !== 'Normal')) {
            return 'partial';
        }

        return 'conflict';
    }

    private function gateReasons(?array $rawFeatures, mixed $storedReasons): array
    {
        if (is_array($storedReasons)) {
            return array_values($storedReasons);
        }

        if (is_array($rawFeatures) && is_array($rawFeatures['gate_reasons'] ?? null)) {
            return array_values($rawFeatures['gate_reasons']);
        }

        return [];
    }

    private function chartData(float $logicScore, float $aiConfidence, string $agreement, mixed $storedChart): array
    {
        $chart = is_array($storedChart) ? $storedChart : [];
        $chart['logic_vs_ai'] = [
            ['label' => 'Logic Score', 'value' => round($logicScore, 2)],
            ['label' => 'AI Confidence', 'value' => round($aiConfidence, 2)],
        ];
        $chart['agreement'] = $agreement;

        return $chart;
    }
}
