<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Services\AuditService;
use App\Services\ToolProfileService;
use App\Services\VmLabExperimentTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExperimentController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private ToolProfileService $toolProfiles,
        private VmLabExperimentTemplateService $vmLabTemplates,
    ) {
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

        if ($profile = $request->get('tool_profile')) {
            $query->where('tool_profile', $this->toolProfiles->normalize($profile));
        }

        if ($targetPlatform = $request->get('target_platform')) {
            $query->where('target_platform', $targetPlatform);
        }

        $experiments = $query->latest()->paginate(15)->withQueryString();
        $toolProfiles = $this->toolProfiles->options();

        return view('experiments.index', compact('experiments', 'toolProfiles'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('experiments.create', [
            'toolProfiles' => $this->toolProfiles->options(),
        ]);
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
            'tool_profile'      => ['required', Rule::in($this->toolProfiles->keys())],
            'attack_pattern'    => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'analysis_profile_key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'target_platform'   => ['nullable', 'string', 'max:120'],
            'traffic_type'      => ['required', Rule::in($this->truthLabelOptions())],
            'ground_truth_label'=> ['nullable', Rule::in($this->truthLabelOptions())],
        ]);

        $code = 'EXP-' . str_pad((string) (Experiment::max('id') + 1), 3, '0', STR_PAD_LEFT);
        $data['experiment_code'] = $code;
        $data['user_id'] = auth()->id();
        $data['tool_profile'] = $this->toolProfiles->normalize($data['tool_profile'] ?? null);
        $profile = $this->toolProfiles->get($data['tool_profile']);
        $data['attack_pattern'] = $data['attack_pattern'] ?: ($profile['default_attack_pattern'] ?? null);
        $data['analysis_profile_key'] = $data['analysis_profile_key'] ?: $data['tool_profile'];
        $data['target_platform'] = $data['target_platform'] ?: 'vm_ubuntu_server';

        $experiment = Experiment::create($data);
        $this->audit->log('experiment.created', $experiment);

        return redirect()->route('experiments.show', $experiment)
            ->with('success', 'Eksperimen baru berhasil dibuat.');
    }

    public function createVmDrafts()
    {
        $this->authorizeAdmin();

        $result = $this->vmLabTemplates->createMissingDrafts(auth()->id());
        foreach ($result['created'] as $experiment) {
            $this->audit->log('experiment.vm_draft_created', $experiment, [
                'tool_profile' => $experiment->tool_profile,
                'target_platform' => $experiment->target_platform,
            ]);
        }

        return redirect()->route('experiments.index', ['target_platform' => 'vm_ubuntu_server'])
            ->with(
                'success',
                count($result['created']) . ' draft eksperimen VM dibuat, '
                . count($result['existing']) . ' sudah tersedia. Data serangan belum diisi.'
            );
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
        return view('experiments.edit', [
            'experiment' => $experiment,
            'toolProfiles' => $this->toolProfiles->options(),
        ]);
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
            'tool_profile'      => ['required', Rule::in($this->toolProfiles->keys())],
            'attack_pattern'    => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'analysis_profile_key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'target_platform'   => ['nullable', 'string', 'max:120'],
            'traffic_type'      => ['required', Rule::in($this->truthLabelOptions())],
            'ground_truth_label'=> ['nullable', Rule::in($this->truthLabelOptions())],
        ]);

        $data['tool_profile'] = $this->toolProfiles->normalize($data['tool_profile'] ?? null);
        $profile = $this->toolProfiles->get($data['tool_profile']);
        $data['attack_pattern'] = $data['attack_pattern'] ?: ($profile['default_attack_pattern'] ?? null);
        $data['analysis_profile_key'] = $data['analysis_profile_key'] ?: $data['tool_profile'];
        $data['target_platform'] = $data['target_platform'] ?: 'vm_ubuntu_server';

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

    private function truthLabelOptions(): array
    {
        return array_values(array_unique(array_merge(
            ['normal', 'slowloris_lab', 'mixed', 'unknown'],
            $this->toolProfiles->keys()
        )));
    }
}
