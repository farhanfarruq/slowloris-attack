<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Services\AiValidationService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AiValidationController extends Controller
{
    public function __construct(
        private AiValidationService $ai,
        private AuditService $audit,
    ) {
    }

    public function index()
    {
        $experiments = Experiment::with(['extractedFeature', 'aiResults'])
            ->latest()->paginate(15);
        $providers = $this->ai->listProviders();

        return view('ai.index', compact('experiments', 'providers'));
    }

    public function show(Experiment $experiment)
    {
        $providers = $this->ai->listProviders();
        $results = $this->ai->latestResults($experiment);
        $vote = $this->ai->vote($experiment);

        return view('ai.show', compact('experiment', 'providers', 'vote', 'results'));
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
                'providers' => 'Provider belum siap untuk live AI: ' . implode(', ', $notReady)
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
                'providers' => 'Validasi AI gagal: ' . $e->getMessage(),
            ]);
        }

        $this->audit->log('ai.validated', $experiment, [
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
            'results'        => $results,
            'voting'         => $vote,
        ]);
    }

    private function pairingError(Experiment $experiment): ?string
    {
        $acquisition = $experiment->acquisitionFiles()->latest()->first();

        if (!$acquisition) {
            return 'Validasi AI ditolak: upload file akuisisi dulu.';
        }

        $validation = $experiment->validationFiles()
            ->where('acquisition_file_id', $acquisition->id)
            ->latest()
            ->first();

        if (!$validation) {
            return 'Validasi AI ditolak: file validasi Snort harus dipasangkan dengan file akuisisi terbaru.';
        }

        return null;
    }
}
