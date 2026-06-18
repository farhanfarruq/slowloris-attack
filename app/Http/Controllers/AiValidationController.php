<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Services\AiValidationService;
use App\Services\AnalysisComparisonService;
use App\Services\AuditService;
use App\Services\ToolProfileService;
use Illuminate\Http\Request;

class AiValidationController extends Controller
{
    public function __construct(
        private AiValidationService $ai,
        private AuditService $audit,
        private ToolProfileService $toolProfiles,
        private AnalysisComparisonService $comparison,
    ) {
    }

    public function index(Request $request)
    {
        $experiments = Experiment::with(['extractedFeature', 'aiResults'])
            ->when($request->get('tool_profile'), fn ($q, $profile) => $q->where('tool_profile', $this->toolProfiles->normalize($profile)))
            ->latest()->paginate(15)->withQueryString();
        $providers = $this->ai->listProviders();
        $toolProfiles = $this->toolProfiles->options();

        return view('ai.index', compact('experiments', 'providers', 'toolProfiles'));
    }

    public function show(Experiment $experiment)
    {
        $providers = $this->ai->listProviders();
        $results = $this->ai->latestResults($experiment);
        $vote = $this->ai->vote($experiment);
        $comparisons = $this->comparison->forExperiment($experiment);

        return view('ai.show', compact('experiment', 'providers', 'vote', 'results', 'comparisons'));
    }

    public function run(Request $request, Experiment $experiment)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $request->validate([
            'providers'   => ['nullable', 'array'],
            'providers.*' => ['required', 'string'],
        ]);

        $available = collect($this->ai->listProviders())->keyBy('key');
        $providers = array_values(array_intersect($data['providers'] ?? [], $available->keys()->all()));

        if (empty($providers)) {
            return back()->withErrors([
                'providers' => 'Belum ada provider AI live yang dipilih atau siap. Isi API key, aktifkan live API, lalu pilih provider.',
            ]);
        }

        $notReady = collect($providers)
            ->filter(fn (string $key) => !($available[$key]['can_run'] ?? false))
            ->map(fn (string $key) => $available[$key]['label'] ?? $key)
            ->values()
            ->all();

        if (!empty($notReady)) {
            return back()->withErrors([
                'providers' => 'Provider belum siap untuk live AI Analysis: ' . implode(', ', $notReady)
                    . '. Isi API key dan aktifkan live API terlebih dahulu.',
            ]);
        }

        if ($error = $this->pairingError($experiment)) {
            return back()->withErrors(['providers' => $error]);
        }

        try {
            $results = $this->ai->runForExperiment($experiment, $providers);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'providers' => 'AI Analysis gagal: ' . $e->getMessage(),
            ]);
        }

        $this->audit->log('ai.analyzed', $experiment, [
            'providers' => $providers,
            'count'     => count($results),
        ]);

        return redirect()->route('ai.show', $experiment)
            ->with('success', count($results) . ' model AI selesai dijalankan.');
    }

    public function exportJson(Experiment $experiment)
    {
        $results = $this->ai->latestResults($experiment);
        $vote = $this->ai->vote($experiment);

        return response()->json([
            'experiment_id'  => $experiment->experiment_code,
            'experiment_name'=> $experiment->name,
            'tool_profile'   => $experiment->tool_profile,
            'attack_pattern' => $experiment->attack_pattern,
            'results'        => $results,
            'voting'         => $vote,
        ]);
    }

    private function pairingError(Experiment $experiment): ?string
    {
        $acquisition = $experiment->acquisitionFiles()->latest()->first();

        if (!$acquisition) {
            return 'AI Analysis ditolak: upload file akuisisi dulu.';
        }

        $validation = $experiment->validationFiles()
            ->where('acquisition_file_id', $acquisition->id)
            ->latest()
            ->first();

        if (!$validation) {
            return 'AI Analysis ditolak: file validasi Snort harus dipasangkan dengan file akuisisi terbaru.';
        }

        return null;
    }
}
