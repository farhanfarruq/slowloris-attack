<?php

namespace App\Services;

use App\Models\Experiment;

class DatasetCoverageService
{
    public function __construct(private ?ExperimentQualityService $quality = null)
    {
        $this->quality ??= new ExperimentQualityService();
    }

    public function summary(): array
    {
        $profiles = collect(config('tool_profiles.profiles', []))
            ->map(fn (array $profile, string $key) => [
                'key' => $key,
                'label' => $profile['label'] ?? strtoupper($key),
                'attack_samples' => 0,
                'normal_samples' => 0,
                'false_positive_guard_samples' => 0,
                'ready_for_evaluation' => 0,
                'needs_review' => 0,
                'incomplete' => 0,
                'status' => 'Incomplete',
                'missing_evidence' => [],
            ])
            ->keyBy('key')
            ->all();

        $experiments = Experiment::with('extractedFeature')
            ->withCount(['acquisitionFiles', 'validationFiles'])
            ->get();

        foreach ($experiments as $experiment) {
            $profileKey = $this->bucketProfile($experiment);
            if (!isset($profiles[$profileKey])) {
                continue;
            }

            $quality = $this->quality->evaluate($experiment);
            $groundTruth = strtolower((string) $experiment->ground_truth_label);

            if ($groundTruth === 'normal') {
                $profiles[$profileKey]['normal_samples']++;
            } elseif ($groundTruth !== '' && $groundTruth !== 'unknown') {
                $profiles[$profileKey]['attack_samples']++;
            }

            if ($this->isFalsePositiveGuardSample($experiment)) {
                $profiles[$profileKey]['false_positive_guard_samples']++;
            }

            match ($quality['status']) {
                'Ready' => $profiles[$profileKey]['ready_for_evaluation']++,
                'Needs Review' => $profiles[$profileKey]['needs_review']++,
                default => $profiles[$profileKey]['incomplete']++,
            };

            foreach ($quality['reasons'] as $reason) {
                $profiles[$profileKey]['missing_evidence'][$reason] = true;
            }
        }

        foreach ($profiles as &$profile) {
            $hasAttack = $profile['attack_samples'] > 0;
            $hasNormal = $profile['normal_samples'] > 0 || $profile['false_positive_guard_samples'] > 0;
            $profile['status'] = $hasAttack && $hasNormal ? 'Ready' : ($hasAttack || $hasNormal ? 'Needs Review' : 'Incomplete');
            $profile['missing_evidence'] = array_keys($profile['missing_evidence']);
        }
        unset($profile);

        return array_values($profiles);
    }

    private function bucketProfile(Experiment $experiment): string
    {
        $groundTruth = strtolower((string) $experiment->ground_truth_label);
        $aliases = [
            'slowloris_lab' => 'slowloris',
            'slow_http' => 'slowloris',
            'slowloris' => 'slowloris',
            'loic' => 'loic',
            'hoic' => 'hoic',
            'hping3' => 'hping3',
            'torshammer' => 'torshammer',
            'xerxes' => 'xerxes',
        ];

        if (isset($aliases[$groundTruth])) {
            return $aliases[$groundTruth];
        }

        return $experiment->tool_profile
            ?: $experiment->getAttribute('analysis_profile_key')
            ?: 'slowloris';
    }

    private function isFalsePositiveGuardSample(Experiment $experiment): bool
    {
        $text = strtolower(implode(' ', [
            $experiment->ground_truth_label,
            $experiment->scenario_key,
            $experiment->traffic_type,
            $experiment->attack_pattern,
            $experiment->name,
        ]));

        foreach (['normal', 'baseline', 'http-burst', 'http burst', 'iperf', 'bandwidth', 'portscan', 'port scan'] as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
