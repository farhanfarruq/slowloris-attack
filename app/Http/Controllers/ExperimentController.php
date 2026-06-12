<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ExperimentController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(Request $request)
    {
        $query = Experiment::with(['user', 'extractedFeature', 'aiResults']);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%$search%")
                  ->orWhere('experiment_code', 'like', "%$search%");
        }

        if ($type = $request->get('traffic_type')) {
            $query->where('traffic_type', $type);
        }

        $experiments = $query->latest()->paginate(15)->withQueryString();

        return view('experiments.index', compact('experiments'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('experiments.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'experiment_date'   => ['required', 'date'],
            'network_interface' => ['nullable', 'string', 'max:64'],
            'target_ip'         => ['nullable', 'ip'],
            'source_ip'         => ['nullable', 'ip'],
            'capture_duration'  => ['nullable', 'integer', 'min:1', 'max:86400'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'scenario_key'      => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'traffic_type'      => ['required', 'in:normal,slowloris_lab,mixed,unknown'],
            'ground_truth_label'=> ['nullable', 'in:normal,slowloris_lab,mixed,unknown'],
        ]);

        $code = 'EXP-' . str_pad((string) (Experiment::max('id') + 1), 3, '0', STR_PAD_LEFT);
        $data['experiment_code'] = $code;
        $data['user_id'] = auth()->id();

        $experiment = Experiment::create($data);
        $this->audit->log('experiment.created', $experiment);

        return redirect()->route('experiments.show', $experiment)
            ->with('success', 'Eksperimen baru berhasil dibuat.');
    }

    public function show(Experiment $experiment)
    {
        $experiment->load([
            'user',
            'acquisitionFiles',
            'validationFiles.acquisitionFile',
            'snortAlerts' => fn ($q) => $q->latest()->limit(50),
            'extractedFeature',
            'aiResults' => fn ($q) => $q->latest(),
            'reviewerNotes.user',
            'finalReports',
        ]);

        return view('experiments.show', compact('experiment'));
    }

    public function edit(Experiment $experiment)
    {
        $this->authorizeAdmin();
        return view('experiments.edit', compact('experiment'));
    }

    public function update(Request $request, Experiment $experiment)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'experiment_date'   => ['required', 'date'],
            'network_interface' => ['nullable', 'string', 'max:64'],
            'target_ip'         => ['nullable', 'ip'],
            'source_ip'         => ['nullable', 'ip'],
            'capture_duration'  => ['nullable', 'integer', 'min:1', 'max:86400'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'scenario_key'      => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'traffic_type'      => ['required', 'in:normal,slowloris_lab,mixed,unknown'],
            'ground_truth_label'=> ['nullable', 'in:normal,slowloris_lab,mixed,unknown'],
        ]);

        $experiment->update($data);
        $this->audit->log('experiment.updated', $experiment);

        return back()->with('success', 'Eksperimen berhasil diperbarui.');
    }

    public function destroy(Experiment $experiment)
    {
        $this->authorizeAdmin();
        $this->audit->log('experiment.deleted', $experiment, ['name' => $experiment->name]);
        $experiment->delete();

        return redirect()->route('experiments.index')
            ->with('success', 'Eksperimen dihapus.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Hanya peneliti yang dapat melakukan aksi ini.');
    }
}
