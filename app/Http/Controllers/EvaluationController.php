<?php

namespace App\Http\Controllers;

use App\Models\CalibrationSnapshot;
use App\Services\AuditService;
use App\Services\AiDisagreementService;
use App\Services\DatasetCoverageService;
use App\Services\EvaluationExportService;
use App\Services\EvaluationMetricsService;
use App\Services\FalsePositiveGuardService;
use App\Services\ProfileCalibrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Halaman evaluasi: confusion matrix global dan per tool profile.
 * Evaluasi ini mengukur keputusan final program terhadap ground truth lab.
 */
class EvaluationController extends Controller
{
    public function index(
        Request $request,
        EvaluationMetricsService $evaluation,
        DatasetCoverageService $datasetCoverage,
        FalsePositiveGuardService $falsePositiveGuards,
        AiDisagreementService $aiDisagreements,
        ProfileCalibrationService $calibration,
    )
    {
        $profiles = collect(config('tool_profiles.profiles', []))
            ->map(fn (array $profile, string $key) => [
                'key' => $key,
                'label' => $profile['label'] ?? strtoupper($key),
            ])
            ->values()
            ->all();
        $profileKeys = array_column($profiles, 'key');
        $selectedProfile = in_array($request->query('calibration_profile'), $profileKeys, true)
            ? $request->query('calibration_profile')
            : ($profileKeys[0] ?? 'slowloris');
        $detectedThreshold = min(100, max(0, (float) $request->query('detected', 76)));
        $suspiciousThreshold = min($detectedThreshold, max(0, (float) $request->query('suspicious', 56)));

        return view('evaluation.index', array_merge($evaluation->summary(), [
            'datasetCoverage' => $datasetCoverage->summary(),
            'falsePositiveGuards' => $falsePositiveGuards->summary(),
            'aiDisagreements' => $aiDisagreements->summary(),
            'calibrationProfiles' => $profiles,
            'calibrationResult' => $calibration->simulate($selectedProfile, $detectedThreshold, $suspiciousThreshold),
            'calibrationSnapshots' => Schema::hasTable('calibration_snapshots')
                ? CalibrationSnapshot::with('user')->latest()->limit(5)->get()
                : collect(),
        ]));
    }

    public function storeCalibrationSnapshot(
        Request $request,
        ProfileCalibrationService $calibration,
        AuditService $audit,
    ) {
        abort_unless($request->user()?->isAdmin(), 403);

        if (!Schema::hasTable('calibration_snapshots')) {
            return back()->with('error', 'Tabel calibration_snapshots belum tersedia. Jalankan php artisan migrate.');
        }

        $profiles = array_keys(config('tool_profiles.profiles', []));
        $data = $request->validate([
            'calibration_profile' => ['required', 'string', 'in:' . implode(',', $profiles)],
            'detected' => ['required', 'numeric', 'min:0', 'max:100'],
            'suspicious' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $detectedThreshold = (float) $data['detected'];
        $suspiciousThreshold = min($detectedThreshold, (float) $data['suspicious']);
        $result = $calibration->simulate($data['calibration_profile'], $detectedThreshold, $suspiciousThreshold);

        $snapshot = CalibrationSnapshot::create([
            'user_id' => $request->user()->id,
            'tool_profile' => $result['profile'],
            'detected_threshold' => $result['detected_threshold'],
            'suspicious_threshold' => $result['suspicious_threshold'],
            'metrics' => $result['metrics'],
            'changed_rows' => $result['changed'],
        ]);

        $audit->log('calibration.snapshot_saved', $snapshot, [
            'tool_profile' => $snapshot->tool_profile,
            'detected_threshold' => $snapshot->detected_threshold,
            'suspicious_threshold' => $snapshot->suspicious_threshold,
        ]);

        return redirect()
            ->route('evaluation.index', [
                'calibration_profile' => $snapshot->tool_profile,
                'detected' => $snapshot->detected_threshold,
                'suspicious' => $snapshot->suspicious_threshold,
            ])
            ->with('success', 'Snapshot calibration disimpan. Scoring asli tidak berubah.');
    }

    public function export(
        string $format,
        EvaluationMetricsService $evaluation,
        DatasetCoverageService $datasetCoverage,
        EvaluationExportService $exporter,
    ) {
        abort_unless(in_array($format, ['json', 'csv', 'md'], true), 404);

        $content = $exporter->make($evaluation->summary(), $datasetCoverage->summary(), $format);
        $extension = $format === 'md' ? 'md' : $format;
        $type = match ($format) {
            'csv' => 'text/csv',
            'md' => 'text/markdown',
            default => 'application/json',
        };

        return response($content, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'attachment; filename="evaluation-' . now()->format('Ymd-His') . '.' . $extension . '"',
        ]);
    }

    public function exportCalibration(string $format, ProfileCalibrationService $calibration, Request $request)
    {
        abort_unless(in_array($format, ['json', 'md'], true), 404);

        $profiles = array_keys(config('tool_profiles.profiles', []));
        $profile = in_array($request->query('calibration_profile'), $profiles, true)
            ? $request->query('calibration_profile')
            : ($profiles[0] ?? 'slowloris');
        $detectedThreshold = min(100, max(0, (float) $request->query('detected', 76)));
        $suspiciousThreshold = min($detectedThreshold, max(0, (float) $request->query('suspicious', 56)));
        $content = $calibration->export(
            $calibration->simulate($profile, $detectedThreshold, $suspiciousThreshold),
            $format
        );

        return response($content, 200, [
            'Content-Type' => $format === 'json' ? 'application/json' : 'text/markdown',
            'Content-Disposition' => 'attachment; filename="calibration-' . $profile . '-' . now()->format('Ymd-His') . '.' . $format . '"',
        ]);
    }
}
