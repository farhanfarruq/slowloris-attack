<?php

namespace App\Services;

use App\Models\Experiment;

class ExperimentEvidenceService
{
    public function summary(Experiment $experiment): array
    {
        $experiment->loadMissing([
            'acquisitionFiles',
            'validationFiles',
            'snortAlerts',
            'extractedFeature',
            'aiResults',
        ]);

        $features = $experiment->extractedFeature;
        $raw = is_array($features?->raw_features) ? $features->raw_features : [];
        $gates = is_array($raw['evidence_gates'] ?? null) ? $raw['evidence_gates'] : [];
        $latestAi = $experiment->aiResults->sortByDesc('created_at')->first();

        return [
            'metadata' => [
                'code' => $experiment->experiment_code,
                'name' => $experiment->name,
                'tool_profile' => $experiment->tool_profile ?: ($raw['tool_profile'] ?? 'slowloris'),
                'ground_truth' => $experiment->ground_truth_label ?: 'missing',
                'status' => $experiment->experiment_status,
            ],
            'decision' => [
                'final_score' => $features?->final_attack_score,
                'category' => $features?->attack_category,
                'logic_classification' => $raw['logic_classification'] ?? $features?->attack_category,
                'final_decision' => $raw['final_decision'] ?? $experiment->experiment_status,
            ],
            'acquisition' => [
                'files' => $experiment->acquisitionFiles->count(),
                'total_packets' => $experiment->acquisitionFiles->sum(fn ($file) => (int) ($file->total_packets ?? 0)),
                'tcp_packets' => $experiment->acquisitionFiles->sum(fn ($file) => (int) ($file->tcp_packets ?? 0)),
                'http_packets' => $experiment->acquisitionFiles->sum(fn ($file) => (int) ($file->http_packets ?? 0)),
                'connections' => $experiment->acquisitionFiles->sum(fn ($file) => (int) ($file->total_connections ?? 0)),
            ],
            'validation' => [
                'files' => $experiment->validationFiles->count(),
                'total_alerts' => $experiment->validationFiles->sum(fn ($file) => (int) ($file->total_alerts ?? 0)),
                'highest_severity' => $this->highestSeverity($experiment),
                'stored_alerts' => $experiment->snortAlerts->count(),
            ],
            'gates' => [
                'passed' => $this->gateNames($gates, true),
                'failed' => $this->gateNames($gates, false),
                'reasons' => array_values((array) ($raw['gate_reasons'] ?? $latestAi?->logic_gate_reasons ?? [])),
                'missing' => array_values((array) ($raw['missing_evidence'] ?? $latestAi?->missing_evidence ?? [])),
            ],
            'ai' => $latestAi ? [
                'model' => $latestAi->model_name,
                'classification' => $latestAi->classification,
                'confidence' => $latestAi->confidence_score,
                'reason' => $latestAi->reason,
                'simulated' => (bool) $latestAi->is_simulated,
            ] : null,
        ];
    }

    public function markdown(Experiment $experiment): string
    {
        $data = $this->summary($experiment);
        $lines = [
            '# Evidence Bundle ' . $data['metadata']['code'],
            '',
            '| Field | Value |',
            '|---|---|',
            '| Name | ' . $data['metadata']['name'] . ' |',
            '| Tool profile | ' . $data['metadata']['tool_profile'] . ' |',
            '| Ground truth | ' . $data['metadata']['ground_truth'] . ' |',
            '| Status | ' . $data['metadata']['status'] . ' |',
            '| Final score | ' . ($data['decision']['final_score'] ?? 'missing') . ' |',
            '| Category | ' . ($data['decision']['category'] ?? 'missing') . ' |',
            '| Final decision | ' . ($data['decision']['final_decision'] ?? 'missing') . ' |',
            '',
            '## Acquisition',
            '',
            '- Files: ' . $data['acquisition']['files'],
            '- Total packets: ' . $data['acquisition']['total_packets'],
            '- TCP packets: ' . $data['acquisition']['tcp_packets'],
            '- HTTP packets: ' . $data['acquisition']['http_packets'],
            '- Connections: ' . $data['acquisition']['connections'],
            '',
            '## Validation',
            '',
            '- Files: ' . $data['validation']['files'],
            '- Total alerts: ' . $data['validation']['total_alerts'],
            '- Highest severity: ' . ($data['validation']['highest_severity'] ?? 'missing'),
            '- Stored alerts: ' . $data['validation']['stored_alerts'],
            '',
            '## Evidence Gates',
            '',
            '- Passed: ' . ($data['gates']['passed'] ? implode(', ', $data['gates']['passed']) : 'none'),
            '- Failed: ' . ($data['gates']['failed'] ? implode(', ', $data['gates']['failed']) : 'none'),
            '- Missing: ' . ($data['gates']['missing'] ? implode(', ', $data['gates']['missing']) : 'none'),
            '',
            '## Gate Reasons',
            '',
        ];

        foreach ($data['gates']['reasons'] ?: ['none'] as $reason) {
            $lines[] = '- ' . $this->stringify($reason);
        }

        $lines[] = '';
        $lines[] = '## AI Validation';
        $lines[] = '';

        if ($data['ai']) {
            $lines[] = '- Model: ' . $data['ai']['model'];
            $lines[] = '- Classification: ' . $data['ai']['classification'];
            $lines[] = '- Confidence: ' . $data['ai']['confidence'] . '%';
            $lines[] = '- Reason: ' . ($data['ai']['reason'] ?: 'missing');
        } else {
            $lines[] = '- Belum ada hasil AI.';
        }

        return implode("\n", $lines) . "\n";
    }

    private function gateNames(array $gates, bool $expected): array
    {
        $names = [];
        foreach ($gates as $name => $value) {
            if (is_bool($value) && $value === $expected) {
                $names[] = (string) $name;
            }
        }

        return $names;
    }

    private function highestSeverity(Experiment $experiment): ?string
    {
        $rank = ['high' => 3, 'medium' => 2, 'low' => 1];

        return $experiment->validationFiles
            ->pluck('highest_severity')
            ->filter()
            ->sortByDesc(fn (string $severity) => $rank[strtolower($severity)] ?? 0)
            ->first();
    }

    private function stringify(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES);
    }
}
