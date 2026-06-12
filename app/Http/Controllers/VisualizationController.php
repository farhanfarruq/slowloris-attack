<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Models\SnortAlert;
use Illuminate\Support\Facades\DB;

class VisualizationController extends Controller
{
    public function index(?Experiment $experiment = null)
    {
        $experiments = Experiment::orderBy('experiment_date', 'desc')->get();
        $selected = $experiment?->id ? $experiment : $experiments->first();

        if (!$selected) {
            return view('visualization.index', [
                'experiments' => $experiments,
                'selected'    => null,
                'datasets'    => null,
            ]);
        }

        $selected->load(['extractedFeature', 'acquisitionFiles', 'validationFiles']);

        $datasets = [
            'radar' => $selected->extractedFeature?->radarScores() ?? [],
            'severity' => $this->severityCount($selected->id),
            'protocol' => $this->protocolDistribution($selected),
            'timeline' => $this->packetTimeline($selected),
            'connections_over_time' => $this->connectionsOverTime($selected),
            'heatmap' => $this->heatmap($selected->id),
            'final_score' => $selected->extractedFeature?->final_attack_score,
            'attack_category' => $selected->extractedFeature?->attack_category,
        ];

        return view('visualization.index', [
            'experiments' => $experiments,
            'selected'    => $selected,
            'datasets'    => $datasets,
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
}
