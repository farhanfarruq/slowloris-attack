<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Models\SnortAlert;
use App\Services\AnalysisComparisonService;
use App\Services\ToolProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisualizationController extends Controller
{
    public function __construct(
        private ToolProfileService $toolProfiles,
        private AnalysisComparisonService $comparison,
    ) {
    }

    public function index(Request $request, ?Experiment $experiment = null)
    {
        $experiments = Experiment::query()
            ->when($request->get('tool_profile'), fn ($q, $profile) => $q->where('tool_profile', $this->toolProfiles->normalize($profile)))
            ->orderBy('experiment_date', 'desc')
            ->get();
        $selected = $experiment?->id ? $experiment : $experiments->first();

        if (!$selected) {
            return view('visualization.index', [
                'experiments' => $experiments,
                'selected'    => null,
                'datasets'    => null,
                'toolProfiles' => $this->toolProfiles->options(),
            ]);
        }

        $selected->load(['extractedFeature', 'acquisitionFiles', 'validationFiles', 'aiResults']);
        $latestAi = $selected->aiResults()->where('is_simulated', false)->latest()->first();
        $comparison = $this->comparison->summarize($selected, $latestAi);

        $datasets = [
            'radar' => $selected->extractedFeature?->radarScores() ?? [],
            'severity' => $this->severityCount($selected->id),
            'protocol' => $this->protocolDistribution($selected),
            'timeline' => $this->packetTimeline($selected),
            'connections_over_time' => $this->connectionsOverTime($selected),
            'heatmap' => $this->heatmap($selected->id),
            'ai' => $this->aiAnalysisChart($selected),
            'comparison' => $comparison['chart_data'] ?? [],
            'final_score' => $selected->extractedFeature?->final_attack_score,
            'attack_category' => $selected->extractedFeature?->attack_category,
            'tool_profile' => $selected->tool_profile ?? 'slowloris',
            'attack_pattern' => $selected->attack_pattern ?? $selected->scenario_key,
        ];

        return view('visualization.index', [
            'experiments' => $experiments,
            'selected'    => $selected,
            'datasets'    => $datasets,
            'toolProfiles' => $this->toolProfiles->options(),
        ]);
    }

    private function severityCount(int $expId): array
    {
        return SnortAlert::where('experiment_id', $expId)
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->all();
    }

    private function protocolDistribution(Experiment $experiment): array
    {
        $acq = $experiment->acquisitionFiles()->latest()->first();
        return is_array($acq?->protocol_distribution) ? $acq->protocol_distribution : [];
    }

    private function packetTimeline(Experiment $experiment): array
    {
        // Generate sintetis dari fitur agar tampilan tetap muncul saat data minimal.
        $features = $experiment->extractedFeature;
        $duration = max(60, (int) ($features?->duration_seconds ?? 600));
        $totalPackets = max(0, (int) ($features?->total_packets ?? 0));
        $points = 30;
        $perBucket = (int) round($totalPackets / $points);

        $series = [];
        for ($i = 0; $i < $points; $i++) {
            $jitter = $perBucket > 0 ? rand(-(int) ($perBucket * 0.2), (int) ($perBucket * 0.2)) : 0;
            $series[] = [
                'time' => round(($duration / $points) * $i),
                'pps'  => max(0, $perBucket + $jitter),
            ];
        }
        return $series;
    }

    private function connectionsOverTime(Experiment $experiment): array
    {
        $features = $experiment->extractedFeature;
        $points = 20;
        $maxConn = max(0, (int) ($features?->total_connections ?? 0));
        $series = [];
        for ($i = 0; $i < $points; $i++) {
            $series[] = [
                'time' => $i + 1,
                'active' => (int) round($maxConn * (0.4 + 0.6 * sin($i / 4))),
            ];
        }
        return $series;
    }

    private function heatmap(int $expId): array
    {
        $rows = SnortAlert::where('experiment_id', $expId)
            ->select('source_ip', 'destination_port', DB::raw('count(*) as total'))
            ->whereNotNull('source_ip')
            ->whereNotNull('destination_port')
            ->groupBy('source_ip', 'destination_port')
            ->limit(60)->get();

        return $rows->map(fn ($r) => [
            'source_ip' => $r->source_ip,
            'port'      => $r->destination_port,
            'value'     => $r->total,
        ])->all();
    }

    private function aiAnalysisChart(Experiment $experiment): array
    {
        $results = $experiment->aiResults()->where('is_simulated', false)->latest()->limit(8)->get();

        return [
            'confidence_by_provider' => $results->map(fn ($result) => [
                'label' => $result->model_name,
                'confidence' => (float) $result->confidence_score,
                'classification' => $result->classification,
            ])->values()->all(),
            'classification_distribution' => $results->groupBy('classification')
                ->map(fn ($items) => $items->count())
                ->all(),
            'indicator_scores' => $results->flatMap(function ($result) {
                $chart = is_array($result->ai_chart_data) ? $result->ai_chart_data : [];
                return $chart['indicator_scores'] ?? [];
            })->take(10)->values()->all(),
            'evidence_counts' => $results->map(function ($result) {
                $chart = is_array($result->ai_chart_data) ? $result->ai_chart_data : [];
                return $chart['evidence_counts'] ?? [
                    'present' => count((array) $result->supporting_indicators),
                    'missing' => count((array) $result->missing_evidence),
                    'blocking' => count((array) $result->logic_gate_reasons),
                ];
            })->values()->all(),
        ];
    }
}
