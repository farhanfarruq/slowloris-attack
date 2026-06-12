<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Services\AiValidationService;
use App\Services\AnalysisService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function __construct(
        private AnalysisService $analysis,
        private AiValidationService $ai,
        private AuditService $audit,
    ) {
    }

    public function index(Request $request)
    {
        $experiments = Experiment::with(['acquisitionFiles', 'validationFiles', 'extractedFeature'])
            ->latest()->paginate(15);

        return view('analysis.index', compact('experiments'));
    }

    public function process(Experiment $experiment)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($error = $this->pairingError($experiment)) {
            return back()->withErrors(['analysis' => $error]);
        }

        $features = $this->analysis->analyze($experiment);
        $this->audit->log('analysis.processed', $experiment, [
            'final_score' => $features->final_attack_score,
            'category'    => $features->attack_category,
        ]);

        return back()->with('success', "Analisis selesai. Skor: {$features->final_attack_score} ({$features->attack_category}).");
    }

    public function correlate(Experiment $experiment)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($error = $this->pairingError($experiment)) {
            return back()->withErrors(['analysis' => $error]);
        }

        // Sama seperti process: fungsi korelasi sudah include time correlation bonus
        $features = $this->analysis->analyze($experiment);
        $this->audit->log('analysis.correlated', $experiment);

        return back()->with('success', 'Korelasi akuisisi & validasi diperbarui.');
    }

    public function generateReport(Experiment $experiment)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        return redirect()->route('reports.create', $experiment);
    }

    private function pairingError(Experiment $experiment): ?string
    {
        $acquisition = $experiment->acquisitionFiles()->latest()->first();

        if (!$acquisition) {
            return 'Analisis ditolak: upload file akuisisi dulu untuk eksperimen ini.';
        }

        $validation = $experiment->validationFiles()
            ->where('acquisition_file_id', $acquisition->id)
            ->latest()
            ->first();

        if (!$validation) {
            return 'Analisis ditolak: upload file validasi Snort yang dipasangkan ke file akuisisi terbaru.';
        }

        return null;
    }
}
