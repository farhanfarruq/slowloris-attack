<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Models\FinalReport;
use App\Services\AiValidationService;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private AiValidationService $ai,
        private AuditService $audit,
    ) {
    }

    public function index()
    {
        $reports = FinalReport::with(['experiment', 'user'])->latest()->paginate(15);
        return view('reports.index', compact('reports'));
    }

    public function create(Experiment $experiment)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $experiment->load(['extractedFeature', 'aiResults', 'acquisitionFiles', 'validationFiles']);
        $vote = $this->ai->vote($experiment);
        return view('reports.create', compact('experiment', 'vote'));
    }

    public function store(Request $request, Experiment $experiment)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'purpose'         => ['nullable', 'string', 'max:5000'],
            'topology'        => ['nullable', 'string', 'max:5000'],
            'tools_used'      => ['nullable', 'string', 'max:5000'],
            'conclusion'      => ['nullable', 'string', 'max:5000'],
            'limitations'     => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
        ]);

        $vote = $this->ai->vote($experiment);

        // Final decision pada laporan harus konsisten dengan evidence-gated
        // attack_category, BUKAN voting AI. Voting AI hanya pendukung.
        $features = $experiment->extractedFeature;
        $scoring = new \App\Services\ScoringService();
        $reportFinalDecision = $features
            ? $scoring->categoryToFinalDecision($features->attack_category ?? 'Inconclusive', $experiment->tool_profile ?? null)
            : 'Inconclusive';

        // Pastikan keterbatasan terisi dengan disclaimer wajib jika user tidak menulis apa-apa.
        $defaultLimitation = "Eksperimen dijalankan pada lab VM lokal terisolasi (subnet 192.168.56.0/24). "
            . "Hasil ini tidak boleh digeneralisasi ke target publik atau lingkungan produksi. "
            . "Klasifikasi hanya valid bila pasangan file Wireshark .pcapng dan Snort .log "
            . "berasal dari eksperimen yang sama dan tidak dimanipulasi.";

        $finalLimitation = trim((string) ($data['limitations'] ?? ''));
        if ($finalLimitation === '') {
            $data['limitations'] = $defaultLimitation;
        } else {
            // Append disclaimer jika belum ada kalimat tentang lab lokal.
            if (!str_contains(strtolower($finalLimitation), 'lab') || !str_contains(strtolower($finalLimitation), 'lokal')) {
                $data['limitations'] = $finalLimitation . "\n\n" . $defaultLimitation;
            }
        }

        $report = FinalReport::create(array_merge($data, [
            'experiment_id' => $experiment->id,
            'user_id'       => auth()->id(),
            'final_decision'=> $reportFinalDecision,
            'voting_average_confidence' => $vote['voting_average_confidence'],
            'voting_summary'=> array_merge($vote['voting_summary'], [
                'voting_decision'  => $vote['final_decision'],
                'gated_decision'   => $reportFinalDecision,
                'gated_category'   => $features->attack_category ?? null,
                'experiment_status'=> $experiment->experiment_status,
            ]),
        ]));

        $experiment->update(['status' => 'completed']);
        $this->audit->log('report.created', $report);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan berhasil dibuat.');
    }

    public function show(FinalReport $report)
    {
        $report->load(['experiment.extractedFeature', 'experiment.aiResults', 'user']);
        return view('reports.show', compact('report'));
    }

    public function destroy(FinalReport $report)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->audit->log('report.deleted', $report);
        $report->delete();
        return redirect()->route('reports.index')->with('success', 'Laporan dihapus.');
    }

    public function downloadPdf(FinalReport $report)
    {
        $report->load(['experiment.extractedFeature', 'experiment.aiResults']);
        $pdf = Pdf::loadView('reports.pdf', compact('report'));
        return $pdf->download($report->experiment->experiment_code . '_laporan.pdf');
    }

    public function exportFeaturesCsv(Experiment $experiment)
    {
        $features = $experiment->extractedFeature;
        if (!$features) {
            return back()->with('error', 'Belum ada fitur diekstrak.');
        }

        $columns = [
            'experiment_code','total_packets','tcp_packets','http_packets','avg_packet_size',
            'duration_seconds','total_connections','long_lived_connections','avg_connection_duration',
            'connections_to_http_port','throughput_kbps','total_alerts','high_severity_alerts',
            'medium_severity_alerts','baseline_avg_connections','baseline_throughput_kbps',
            'baseline_alert_count','connection_duration_score','header_anomaly_score',
            'low_bandwidth_high_connection_score','snort_alert_score','tcp_connection_score',
            'baseline_deviation_score','ai_confidence_score','final_attack_score','attack_category',
        ];

        $row = [];
        foreach ($columns as $c) {
            $row[$c] = $c === 'experiment_code' ? $experiment->experiment_code : ($features->{$c} ?? '');
        }

        return response()->streamDownload(function () use ($columns, $row) {
            $h = fopen('php://output', 'w');
            fputcsv($h, $columns);
            fputcsv($h, $row);
            fclose($h);
        }, $experiment->experiment_code . '_features.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
