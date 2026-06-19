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

        foreach ($experiments as $exp) {
            $profileKey = $this->profileKey($exp);
            $actual = $this->actualClass($exp);
            $predicted = $this->predictedClass($exp);

            if ($actual === 'unknown' || $predicted === 'unresolved') {
                continue;
            }

            $type = $this->resultType($actual, $predicted);

            $rows[] = [
                'experiment' => $exp,
                'profile_key' => $profileKey,
                'profile_label' => $this->profileLabel($profileKey, $profiles),
                'actual' => $actual,
                'predicted' => $predicted,
                'final_score' => $exp->extractedFeature?->final_attack_score,
                'category' => $exp->extractedFeature?->attack_category,
                'type' => $type,
            ];

            $bucketKey = $actual === 'normal' ? $profileKey : $this->actualProfileKey($exp);
            $bucketKey ??= $profileKey;
            $profileBuckets[$bucketKey][] = $type;
        }

        $metrics = $this->metrics(array_column($rows, 'type'));

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

        return view('evaluation.index', compact('rows', 'metrics', 'profileMetrics', 'coverage'));
    }

    private function metrics(array $types): array
    {
        $tp = count(array_filter($types, fn (string $type) => $type === 'TP'));
        $tn = count(array_filter($types, fn (string $type) => $type === 'TN'));
        $fp = count(array_filter($types, fn (string $type) => $type === 'FP'));
        $fn = count(array_filter($types, fn (string $type) => $type === 'FN'));

        $total = count($types);
        $accuracy  = $total > 0 ? ($tp + $tn) / $total : 0;
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
        $recall    = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
        $f1        = ($precision + $recall) > 0
                     ? 2 * ($precision * $recall) / ($precision + $recall)
                     : 0;

        $metrics = [
            'tp' => $tp, 'tn' => $tn, 'fp' => $fp, 'fn' => $fn,
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

    private function resultType(string $actual, string $predicted): string
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
