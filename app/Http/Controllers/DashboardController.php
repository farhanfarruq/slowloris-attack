<?php

namespace App\Http\Controllers;

use App\Models\AcquisitionFile;
use App\Models\AiResult;
use App\Models\Experiment;
use App\Models\SnortAlert;
use App\Models\ValidationFile;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_acquisition'   => AcquisitionFile::count(),
            'total_validation'    => ValidationFile::count(),
            'total_alerts'        => SnortAlert::count(),
            'suspicious_conn'     => (int) DB::table('extracted_features')
                ->whereNotNull('long_lived_connections')
                ->sum('long_lived_connections'),
            // Rata-rata semua confidence AI (semua kelas, bukan khusus Slowloris).
            'avg_ai_confidence_all'  => round((float) AiResult::where('is_simulated', false)->avg('confidence_score'), 2),
            // Rata-rata confidence khusus klasifikasi "Slowloris Detected".
            'avg_ai_confidence_attack' => round((float) AiResult::where('is_simulated', false)
                ->where('classification', 'Slowloris Detected')
                ->avg('confidence_score'), 2),
            'experiment_total'    => Experiment::count(),
            'experiment_status'   => Experiment::select('experiment_status', DB::raw('count(*) as total'))
                ->groupBy('experiment_status')->pluck('total', 'experiment_status')->all(),
        ];

        $recentExperiments = Experiment::latest()
            ->with(['extractedFeature', 'aiResults'])
            ->take(8)
            ->get();

        $finalDecisionSummary = Experiment::with('extractedFeature')->get()
            ->groupBy(fn ($e) => match ($e->experiment_status) {
                'attack_detected' => 'Serangan asli',
                'normal'          => 'Traffic normal',
                'suspicious'      => 'Perlu validasi lanjutan',
                default           => 'Belum dianalisis',
            })
            ->map->count();

        return view('dashboard', compact('stats', 'recentExperiments', 'finalDecisionSummary'));
    }
}
