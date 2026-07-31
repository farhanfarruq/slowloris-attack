<?php

namespace App\Services;

use App\Models\Experiment;

class ProfileCalibrationService
{
    public function simulate(string $profile, float $detectedThreshold = 76, float $suspiciousThreshold = 56): array
    {
        $rows = [];
        $types = [];

        $experiments = Experiment::query()
            ->with('extractedFeature')
            ->where('tool_profile', $profile)
            ->whereNotNull('ground_truth_label')
            ->get();

        foreach ($experiments as $experiment) {
            $score = (float) ($experiment->extractedFeature?->final_attack_score ?? 0);
            $actual = strtolower((string) $experiment->ground_truth_label) === 'normal' ? 'normal' : 'attack';
            $simulated = $score >= $detectedThreshold
                ? 'attack'
                : ($score >= $suspiciousThreshold ? 'suspicious' : 'normal');
            $type = $this->type($actual, $simulated);
            $types[] = $type;

            if ($this->currentClass($experiment->experiment_status) !== $simulated) {
                $rows[] = [
                    'code' => $experiment->experiment_code,
                    'score' => $score,
                    'current' => $experiment->experiment_status,
                    'simulated' => $simulated,
                    'type' => $type,
                ];
            }
        }

        return [
            'profile' => $profile,
            'detected_threshold' => $detectedThreshold,
            'suspicious_threshold' => $suspiciousThreshold,
            'metrics' => $this->metrics($types),
            'changed' => $rows,
        ];
    }

    public function export(array $result, string $format): string
    {
        return match ($format) {
            'json' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            default => $this->markdown($result),
        };
    }

    private function markdown(array $result): string
    {
        $lines = [
            '# Profile Calibration Snapshot',
            '',
            '| Field | Value |',
            '|---|---:|',
            '| Profile | ' . $result['profile'] . ' |',
            '| Detected threshold | ' . $result['detected_threshold'] . ' |',
            '| Suspicious threshold | ' . $result['suspicious_threshold'] . ' |',
            '| Sample | ' . $result['metrics']['total'] . ' |',
            '| Accuracy | ' . $result['metrics']['accuracy'] . '% |',
            '| Precision | ' . $result['metrics']['precision'] . '% |',
            '| Recall | ' . $result['metrics']['recall'] . '% |',
            '| F1 | ' . $result['metrics']['f1'] . '% |',
            '| Changed rows | ' . count($result['changed']) . ' |',
            '',
            '## Changed Rows',
            '',
            '| Code | Score | Current | Simulated | Type |',
            '|---|---:|---|---|---|',
        ];

        foreach ($result['changed'] as $row) {
            $lines[] = "| {$row['code']} | {$row['score']} | {$row['current']} | {$row['simulated']} | {$row['type']} |";
        }

        if (!$result['changed']) {
            $lines[] = '| - | - | - | - | - |';
        }

        $lines[] = '';
        $lines[] = 'Catatan: snapshot ini hanya simulasi. Data eksperimen asli tidak diubah.';

        return implode("\n", $lines) . "\n";
    }

    private function currentClass(?string $status): string
    {
        return match ($status) {
            'attack_detected' => 'attack',
            'suspicious' => 'suspicious',
            'normal' => 'normal',
            default => 'inconclusive',
        };
    }

    private function type(string $actual, string $predicted): string
    {
        return match (true) {
            $actual === 'attack' && $predicted === 'attack' => 'TP',
            $actual === 'normal' && $predicted === 'normal' => 'TN',
            $actual === 'normal' && in_array($predicted, ['attack', 'suspicious'], true) => 'FP',
            default => 'FN',
        };
    }

    private function metrics(array $types): array
    {
        $tp = count(array_filter($types, fn ($type) => $type === 'TP'));
        $tn = count(array_filter($types, fn ($type) => $type === 'TN'));
        $fp = count(array_filter($types, fn ($type) => $type === 'FP'));
        $fn = count(array_filter($types, fn ($type) => $type === 'FN'));
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
        $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;

        return [
            'total' => count($types),
            'tp' => $tp,
            'tn' => $tn,
            'fp' => $fp,
            'fn' => $fn,
            'accuracy' => count($types) > 0 ? round((($tp + $tn) / count($types)) * 100, 2) : 0,
            'precision' => round($precision * 100, 2),
            'recall' => round($recall * 100, 2),
            'f1' => ($precision + $recall) > 0 ? round((2 * $precision * $recall / ($precision + $recall)) * 100, 2) : 0,
        ];
    }
}
