<?php

namespace App\Services;

use App\Models\Experiment;

class FalsePositiveGuardService
{
    public function summary(): array
    {
        $buckets = [
            'baseline_normal' => $this->blank('Baseline Normal'),
            'http_burst' => $this->blank('HTTP Burst'),
            'iperf_bandwidth' => $this->blank('iPerf Bandwidth'),
            'portscan' => $this->blank('Portscan'),
            'tcp_non_http' => $this->blank('TCP Non-HTTP'),
            'missing_snort' => $this->blank('Missing Snort Evidence'),
        ];

        $experiments = Experiment::query()
            ->withCount('validationFiles')
            ->where(function ($query) {
                $query->where('ground_truth_label', 'normal')
                    ->orWhere('scenario_key', 'like', '%baseline%')
                    ->orWhere('scenario_key', 'like', '%burst%')
                    ->orWhere('scenario_key', 'like', '%iperf%')
                    ->orWhere('scenario_key', 'like', '%portscan%')
                    ->orWhere('name', 'like', '%baseline%')
                    ->orWhere('name', 'like', '%burst%')
                    ->orWhere('name', 'like', '%iperf%')
                    ->orWhere('name', 'like', '%portscan%');
            })
            ->get();

        foreach ($experiments as $experiment) {
            foreach ($this->categories($experiment) as $key) {
                $buckets[$key]['total']++;
                match ($experiment->experiment_status) {
                    'normal' => $buckets[$key]['tn']++,
                    'suspicious' => $buckets[$key]['review']++,
                    'attack_detected' => $buckets[$key]['fp']++,
                    default => $buckets[$key]['pending']++,
                };
            }
        }

        foreach ($buckets as &$bucket) {
            $bucket['false_positive_rate'] = $bucket['total'] > 0
                ? round(($bucket['fp'] / $bucket['total']) * 100, 2)
                : 0;
        }
        unset($bucket);

        return array_values($buckets);
    }

    private function blank(string $label): array
    {
        return [
            'label' => $label,
            'total' => 0,
            'tn' => 0,
            'review' => 0,
            'fp' => 0,
            'pending' => 0,
            'false_positive_rate' => 0,
        ];
    }

    private function categories(Experiment $experiment): array
    {
        $text = strtolower(implode(' ', [
            $experiment->ground_truth_label,
            $experiment->scenario_key,
            $experiment->traffic_type,
            $experiment->attack_pattern,
            $experiment->name,
        ]));

        $categories = [];
        if (str_contains($text, 'baseline') || str_contains($text, 'normal')) {
            $categories[] = 'baseline_normal';
        }
        if (str_contains($text, 'burst')) {
            $categories[] = 'http_burst';
        }
        if (str_contains($text, 'iperf') || str_contains($text, 'bandwidth')) {
            $categories[] = 'iperf_bandwidth';
        }
        if (str_contains($text, 'portscan') || str_contains($text, 'port scan')) {
            $categories[] = 'portscan';
        }
        if (str_contains($text, 'tcp') && !str_contains($text, 'http')) {
            $categories[] = 'tcp_non_http';
        }
        if ((int) $experiment->validation_files_count === 0) {
            $categories[] = 'missing_snort';
        }

        return $categories ?: ['baseline_normal'];
    }
}
