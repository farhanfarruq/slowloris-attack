<?php

namespace App\Services;

class EvaluationExportService
{
    public function make(array $summary, array $coverage, string $format): string
    {
        return match ($format) {
            'csv' => $this->csv($summary),
            'md', 'markdown' => $this->markdown($summary, $coverage),
            default => json_encode([
                'coverage' => $summary['coverage'],
                'profileAware' => $summary['metrics'],
                'binary' => $summary['binaryMetrics'],
                'profileMetrics' => $summary['profileMetrics'],
                'datasetCoverage' => $coverage,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        };
    }

    private function csv(array $summary): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['code', 'name', 'profile', 'actual', 'predicted', 'binary_type', 'profile_type', 'final_score', 'category']);

        foreach ($summary['rows'] as $row) {
            fputcsv($handle, [
                $row['experiment']->experiment_code,
                $row['experiment']->name,
                $row['profile_key'],
                $row['actual_profile_key'] ?? $row['actual'],
                $row['predicted_profile_key'] ?? $row['predicted'],
                $row['binary_type'],
                $row['type'],
                $row['final_score'],
                $row['category'],
            ]);
        }

        rewind($handle);
        return stream_get_contents($handle);
    }

    private function markdown(array $summary, array $coverage): string
    {
        $lines = [
            '# Ringkasan Evaluasi Profile',
            '',
            '| Metric | Value |',
            '|---|---:|',
            '| Accuracy | ' . $summary['metrics']['accuracy'] . '% |',
            '| Precision | ' . $summary['metrics']['precision'] . '% |',
            '| Recall | ' . $summary['metrics']['recall'] . '% |',
            '| F1 | ' . $summary['metrics']['f1'] . '% |',
            '| Samples | ' . $summary['metrics']['total'] . ' |',
            '| Profile mismatch | ' . $summary['metrics']['pm'] . ' |',
            '',
            '## Metrik Per Profile',
            '',
            '| Profile | Sample | TP | TN | FP | FN | PM | Precision | Recall | F1 |',
            '|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|',
        ];

        foreach ($summary['profileMetrics'] as $profile) {
            $lines[] = "| {$profile['label']} | {$profile['total']} | {$profile['tp']} | {$profile['tn']} | {$profile['fp']} | {$profile['fn']} | {$profile['pm']} | {$profile['precision']}% | {$profile['recall']}% | {$profile['f1']}% |";
        }

        $lines[] = '';
        $lines[] = '## Dataset Coverage';
        $lines[] = '';
        $lines[] = '| Profile | Status | Attack | Normal | Guard | Ready | Review | Incomplete |';
        $lines[] = '|---|---|---:|---:|---:|---:|---:|---:|';

        foreach ($coverage as $profile) {
            $lines[] = "| {$profile['label']} | {$profile['status']} | {$profile['attack_samples']} | {$profile['normal_samples']} | {$profile['false_positive_guard_samples']} | {$profile['ready_for_evaluation']} | {$profile['needs_review']} | {$profile['incomplete']} |";
        }

        return implode("\n", $lines) . "\n";
    }
}
