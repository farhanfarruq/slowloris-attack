<?php

namespace App\Http\Controllers;

use App\Models\AcquisitionFile;
use App\Models\Experiment;
use App\Models\SnortAlert;
use App\Models\ValidationFile;
use App\Services\AuditService;
use App\Services\ValidationParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ValidationController extends Controller
{
    public function __construct(
        private ValidationParser $parser,
        private AuditService $audit,
    ) {
    }

    public function index()
    {
        $files = ValidationFile::with(['experiment', 'acquisitionFile'])->latest()->paginate(15);
        $experiments = Experiment::with('acquisitionFiles')->orderBy('experiment_date', 'desc')->get();
        return view('validation.index', compact('files', 'experiments'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $allowed = config('upload.allowed_validation');
        $maxKb = config('upload.max_size_mb') * 1024;

        $data = $request->validate([
            'experiment_id'        => ['required', 'exists:experiments,id'],
            'acquisition_file_id'  => ['required', 'exists:acquisition_files,id'],
            'snort_mode'           => ['required', 'in:ids,ips'],
            'rule_set'             => ['nullable', 'string', 'max:128'],
            'monitoring_interface' => ['nullable', 'string', 'max:64'],
            'threshold'            => ['nullable', 'integer', 'min:0', 'max:100000'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'file'                 => [
                'required',
                'file',
                'max:' . $maxKb,
                function (string $attribute, mixed $value, \Closure $fail) use ($allowed) {
                    $extension = strtolower($value?->getClientOriginalExtension() ?? '');

                    if (!in_array($extension, $allowed, true)) {
                        $fail('File harus berformat: ' . implode(', ', $allowed) . '.');
                    }
                },
            ],
        ]);

        $experiment = Experiment::findOrFail($data['experiment_id']);
        $acquisition = AcquisitionFile::whereKey($data['acquisition_file_id'])
            ->where('experiment_id', $experiment->id)
            ->first();

        if (!$acquisition) {
            return back()
                ->withInput()
                ->withErrors([
                    'acquisition_file_id' => 'File validasi harus dipasangkan dengan file akuisisi dari eksperimen yang sama.',
                ]);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowed, true)) {
            return back()->withErrors(['file' => 'Ekstensi file tidak diizinkan.']);
        }

        $sanitizedName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_');
        $storedRelativeName = now()->format('Ymd_His') . '_' . $sanitizedName . '.' . $extension;
        $directory = 'validation/' . $experiment->id;
        $stored = $file->storeAs($directory, $storedRelativeName, 'local');
        $absolute = Storage::disk('local')->path($stored);

        // Validasi MIME independen.
        $detectedMime = function_exists('mime_content_type') ? @mime_content_type($absolute) : null;
        $expectedMimePrefixes = ['text/', 'application/json', 'application/octet-stream'];
        $mimeOk = false;
        if ($detectedMime) {
            foreach ($expectedMimePrefixes as $prefix) {
                if (str_starts_with($detectedMime, $prefix)) {
                    $mimeOk = true;
                    break;
                }
            }
        } else {
            $mimeOk = true;
        }
        if (!$mimeOk) {
            Storage::disk('local')->delete($stored);
            return back()->withErrors([
                'file' => 'MIME type tidak diharapkan untuk file validasi Snort: ' . $detectedMime,
            ]);
        }

        $summary = $this->parser->parse($absolute, $extension);

        // Bersihkan validation file lama untuk pasangan acquisition yang sama
        // agar tidak terjadi pasangan ganda dan hasil korelasi tetap konsisten.
        $previousValidations = ValidationFile::where('experiment_id', $experiment->id)
            ->where('acquisition_file_id', $acquisition->id)
            ->get();

        foreach ($previousValidations as $old) {
            // Hapus alert dari validation lama sehingga timeCorrelation tidak menumpuk.
            SnortAlert::where('validation_file_id', $old->id)->delete();
            Storage::disk('local')->delete($old->stored_name);
            $this->audit->log('validation.superseded', $old, [
                'replaced_by' => $file->getClientOriginalName(),
            ]);
            $old->delete();
        }

        $vf = ValidationFile::create([
            'experiment_id'         => $experiment->id,
            'acquisition_file_id'   => $acquisition->id,
            'original_name'         => $file->getClientOriginalName(),
            'stored_name'           => $stored,
            'extension'             => $extension,
            'size_bytes'            => $file->getSize(),
            'capture_label'         => $acquisition->capture_label,
            'scenario_key'          => $acquisition->scenario_key,
            'source_ip'             => $acquisition->source_ip,
            'target_ip'             => $acquisition->target_ip,
            'snort_mode'            => $data['snort_mode'],
            'rule_set'              => $data['rule_set'] ?? null,
            'monitoring_interface'  => $data['monitoring_interface'] ?? null,
            'threshold'             => $data['threshold'] ?? null,
            'notes'                 => $data['notes'] ?? null,
            'total_alerts'          => $summary['total_alerts'],
            'dominant_alert_type'   => $summary['dominant_alert_type'],
            'highest_severity'      => $summary['highest_severity'],
            'top_source_ips'        => $summary['top_source_ips'],
            'top_destination_ports' => $summary['top_destination_ports'],
            'alert_timeline'        => $summary['alert_timeline'],
            'matches_slow_http_pattern' => $summary['matches_slow_http_pattern'],
            'parsed_summary'        => array_merge(
                $summary['parsed_summary'] ?? [],
                ['severity_count' => $summary['severity_count']],
            ),
        ]);

        // Persist alerts (max 1000) supaya bisa korelasi waktu di analysis service
        $alertsToInsert = [];
        foreach (array_slice($summary['alerts'], 0, 1000) as $alert) {
            $alertsToInsert[] = [
                'experiment_id'      => $experiment->id,
                'validation_file_id' => $vf->id,
                'alert_timestamp'    => $alert['timestamp']?->toDateTimeString(),
                'alert_type'         => $alert['msg'],
                'severity'           => $alert['severity'],
                'source_ip'          => $alert['src_ip'],
                'source_port'        => $alert['src_port'],
                'destination_ip'     => $alert['dst_ip'],
                'destination_port'   => $alert['dst_port'],
                'protocol'           => $alert['protocol'],
                'message'            => $alert['msg'],
                'raw'                => json_encode($alert['raw'] ?? []),
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }
        if ($alertsToInsert) {
            SnortAlert::insert($alertsToInsert);
        }

        $experiment->update(['status' => 'validated']);
        $this->audit->log('validation.uploaded', $vf, [
            'experiment' => $experiment->experiment_code,
            'extension'  => $extension,
        ]);

        return redirect()->route('experiments.show', $experiment)
            ->with('success', 'File validasi berhasil diunggah dan dipreview.');
    }

    public function destroy(ValidationFile $validation)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        Storage::disk('local')->delete($validation->stored_name);
        SnortAlert::where('validation_file_id', $validation->id)->delete();
        $this->audit->log('validation.deleted', $validation);
        $validation->delete();

        return back()->with('success', 'File validasi dihapus.');
    }
}
