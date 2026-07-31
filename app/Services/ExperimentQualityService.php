<?php

namespace App\Services;

use App\Models\Experiment;

class ExperimentQualityService
{
    public function evaluate(Experiment $experiment): array
    {
        $hard = [];
        $review = [];
        $profile = $this->profileKey($experiment);
        $groundTruth = strtolower((string) $experiment->ground_truth_label);
        $knownProfiles = array_keys(config('tool_profiles.profiles', []));

        if ($groundTruth === '' || $groundTruth === 'unknown') {
            $hard[] = 'ground truth belum siap';
        }

        if (!in_array($profile, $knownProfiles, true)) {
            $hard[] = 'tool profile tidak dikenal';
        }

        if ($this->relationCount($experiment, 'acquisitionFiles', 'acquisition_files_count') === 0) {
            $hard[] = 'file akuisisi belum ada';
        }

        if (in_array($experiment->experiment_status, ['pending', 'inconclusive'], true)) {
            $review[] = 'status analisis belum final';
        }

        if (!$experiment->extractedFeature) {
            $review[] = 'fitur hasil analisis belum ada';
        }

        if ($groundTruth !== '' && $groundTruth !== 'normal'
            && $this->relationCount($experiment, 'validationFiles', 'validation_files_count') === 0) {
            $review[] = 'validasi Snort belum ada';
        }

        $status = $hard ? 'Incomplete' : ($review ? 'Needs Review' : 'Ready');

        return [
            'status' => $status,
            'score' => match ($status) {
                'Ready' => 100,
                'Needs Review' => 70,
                default => 40,
            },
            'reasons' => array_values(array_merge($hard, $review)),
        ];
    }

    private function profileKey(Experiment $experiment): string
    {
        $rawFeatures = $experiment->extractedFeature?->raw_features;

        return $experiment->tool_profile
            ?: $experiment->getAttribute('analysis_profile_key')
            ?: (is_array($rawFeatures) ? ($rawFeatures['tool_profile'] ?? null) : null)
            ?: 'slowloris';
    }

    private function relationCount(Experiment $experiment, string $relation, string $countAttribute): int
    {
        if ($experiment->getAttribute($countAttribute) !== null) {
            return (int) $experiment->getAttribute($countAttribute);
        }

        if ($experiment->relationLoaded($relation)) {
            return $experiment->{$relation}->count();
        }

        return $experiment->{$relation}()->count();
    }
}
