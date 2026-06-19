<?php

namespace App\Http\Controllers;

use App\Models\Experiment;

/**
 * Halaman evaluasi: confusion matrix global dan per tool profile.
 * Evaluasi ini mengukur keputusan final program terhadap ground truth lab.
 */
class EvaluationController extends Controller
{
    public function index()
    {
        $profiles = collect(config('tool_profiles.profiles', []))
            ->map(fn (array $profile, string $key) => [
                'key' => $key,
                'label' => $profile['label'] ?? strtoupper($key),
            ])
            ->values()
            ->all();

        $experiments = Experiment::with('extractedFeature')
            ->whereNotNull('ground_truth_label')
            ->whereIn('experiment_status', ['attack_detected', 'normal', 'suspicious'])
            ->orderBy('experiment_date', 'desc')
            ->get();

        $rows = [];
        $profileBuckets = [];
        $binaryTypes = [];
        $profileTypes = [];

        foreach ($experiments as $exp) {
            $profileKey = $this->profileKey($exp);
            $actual = $this->actualClass($exp);
            $predicted = $this->predictedClass($exp);
            $actualProfileKey = $this->actualProfileKey($exp);
            $predictedProfileKey = $predicted === 'attack' ? $profileKey : null;

            if ($actual === 'unknown' || $predicted === 'unresolved') {
                continue;
            }

            $binaryType = $this->binaryResultType($actual, $predicted);
            $profileType = $this->profileResultType($actual, $predicted, $actualProfileKey, $predictedProfileKey);
            $binaryTypes[] = $binaryType;
            $profileTypes[] = $profileType;

            $rows[] = [
                'experiment' => $exp,
                'profile_key' => $profileKey,
                'profile_label' => $this->profileLabel($profileKey, $profiles),
                'actual' => $actual,
                'predicted' => $predicted,
                'actual_profile_key' => $actualProfileKey,
                'actual_profile_label' => $actualProfileKey ? $this->profileLabel($actualProfileKey, $profiles) : null,
                'predicted_profile_key' => $predictedProfileKey,
                'predicted_profile_label' => $predictedProfileKey ? $this->profileLabel($predictedProfileKey, $profiles) : null,
                'final_score' => $exp->extractedFeature?->final_attack_score,
                'category' => $exp->extractedFeature?->attack_category,
                'binary_type' => $binaryType,
                'type' => $profileType,
            ];

            if ($profileType === 'PM') {
                if ($actualProfileKey) {
                    $profileBuckets[$actualProfileKey][] = 'FN';
                }
                if ($predictedProfileKey) {
                    $profileBuckets[$predictedProfileKey][] = 'FP';
                }
                continue;
            }

            $bucketKey = $actual === 'normal' ? $profileKey : $actualProfileKey;
            $profileBuckets[$bucketKey ?? $profileKey][] = $profileType;
        }

        $metrics = $this->metrics($profileTypes);
        $binaryMetrics = $this->metrics($binaryTypes);

        $profileMetrics = collect($profiles)
            ->map(function (array $profile) use ($profileBuckets) {
                return array_merge($profile, $this->metrics($profileBuckets[$profile['key']] ?? []));
            })
            ->values()
            ->all();

        $coverage = [
            'total_experiments' => Experiment::count(),
            'ready' => count($rows),
            'pending_or_inconclusive' => Experiment::whereIn('experiment_status', ['pending', 'inconclusive'])->count(),
            'missing_ground_truth' => Experiment::whereNull('ground_truth_label')
                ->orWhere('ground_truth_label', '')
                ->orWhere('ground_truth_label', 'unknown')
                ->count(),
        ];

        return view('evaluation.index', compact('rows', 'metrics', 'binaryMetrics', 'profileMetrics', 'coverage'));
    }

    private function metrics(array $types): array
    {
        $tp = count(array_filter($types, fn (string $type) => $type === 'TP'));
        $tn = count(array_filter($types, fn (string $type) => $type === 'TN'));
        $fp = count(array_filter($types, fn (string $type) => $type === 'FP'));
        $fn = count(array_filter($types, fn (string $type) => $type === 'FN'));
        $pm = count(array_filter($types, fn (string $type) => $type === 'PM'));

        $total = count($types);
        $accuracy  = $total > 0 ? ($tp + $tn) / $total : 0;
        $precision = ($tp + $fp + $pm) > 0 ? $tp / ($tp + $fp + $pm) : 0;
        $recall    = ($tp + $fn + $pm) > 0 ? $tp / ($tp + $fn + $pm) : 0;
        $f1        = ($precision + $recall) > 0
                     ? 2 * ($precision * $recall) / ($precision + $recall)
                     : 0;

        $metrics = [
            'tp' => $tp, 'tn' => $tn, 'fp' => $fp, 'fn' => $fn, 'pm' => $pm,
            'total'     => $total,
            'accuracy'  => round($accuracy * 100, 2),
            'precision' => round($precision * 100, 2),
            'recall'    => round($recall * 100, 2),
            'f1'        => round($f1 * 100, 2),
        ];

        return $metrics;
    }

    private function profileKey(Experiment $experiment): string
    {
        $rawFeatures = $experiment->extractedFeature?->raw_features;

        return $experiment->tool_profile
            ?: $experiment->getAttribute('analysis_profile_key')
            ?: (is_array($rawFeatures) ? ($rawFeatures['tool_profile'] ?? null) : null)
            ?: 'slowloris';
    }

    private function actualClass(Experiment $experiment): string
    {
        $label = strtolower((string) $experiment->ground_truth_label);

        if ($label === 'normal') {
            return 'normal';
        }

        return $this->actualProfileKey($experiment) ? 'attack' : 'unknown';
    }

    private function actualProfileKey(Experiment $experiment): ?string
    {
        $label = strtolower((string) $experiment->ground_truth_label);

        if ($label === 'normal' || $label === 'unknown' || $label === '') {
            return null;
        }

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

        if ($label === 'mixed') {
            return $this->profileKey($experiment);
        }

        return $aliases[$label] ?? $this->profileKey($experiment);
    }

    private function predictedClass(Experiment $experiment): string
    {
        return match ($experiment->experiment_status) {
            'attack_detected' => 'attack',
            'normal' => 'normal',
            'suspicious' => 'review',
            default => 'unresolved',
        };
    }

    private function binaryResultType(string $actual, string $predicted): string
    {
        return match (true) {
            $actual === 'attack' && $predicted === 'attack' => 'TP',
            $actual === 'normal' && $predicted === 'normal' => 'TN',
            $actual === 'normal' && in_array($predicted, ['attack', 'review'], true) => 'FP',
            $actual === 'attack' && $predicted === 'normal' => 'FN',
            $actual === 'attack' && $predicted === 'review' => 'FN',
            default => 'FN',
        };
    }

    private function profileResultType(string $actual, string $predicted, ?string $actualProfileKey, ?string $predictedProfileKey): string
    {
        if ($actual === 'normal') {
            return in_array($predicted, ['attack', 'review'], true) ? 'FP' : 'TN';
        }

        if ($predicted !== 'attack') {
            return 'FN';
        }

        return $actualProfileKey !== null && $actualProfileKey === $predictedProfileKey ? 'TP' : 'PM';
    }

    private function profileLabel(string $key, array $profiles): string
    {
        foreach ($profiles as $profile) {
            if ($profile['key'] === $key) {
                return $profile['label'];
            }
        }

        return strtoupper($key);
    }
}
