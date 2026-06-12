<?php

namespace App\Http\Controllers;

use App\Models\AcquisitionFile;
use App\Models\Experiment;
use App\Services\AcquisitionParser;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcquisitionController extends Controller
{
    public function __construct(
        private AcquisitionParser $parser,
        private AuditService $audit,
    ) {
    }

    public function index()
    {
        $files = AcquisitionFile::with('experiment')->latest()->paginate(15);
        $experiments = Experiment::orderBy('experiment_date', 'desc')->get();
        return view('acquisition.index', compact('files', 'experiments'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $allowed = config('upload.allowed_acquisition');
        $maxKb = config('upload.max_size_mb') * 1024;

        $data = $request->validate([
            'experiment_id' => ['required', 'exists:experiments,id'],
            'capture_label' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/'],
            'scenario_key' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'source_ip' => ['nullable', 'ip'],
            'target_ip' => ['nullable', 'ip'],
            'capture_started_at' => ['nullable', 'date'],
            'capture_ended_at' => ['nullable', 'date', 'after_or_equal:capture_started_at'],
            'file'          => [
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
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowed, true)) {
            return back()->withErrors(['file' => 'Ekstensi file tidak diizinkan.']);
        }

        $sanitizedName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_');
        $storedRelativeName = now()->format('Ymd_His') . '_' . $sanitizedName . '.' . $extension;
        $directory = 'acquisition/' . $experiment->id;
        $stored = $file->storeAs($directory, $storedRelativeName, 'local');
        $absolute = Storage::disk('local')->path($stored);

        // Validasi MIME secara independen agar tidak hanya percaya extension dari client.
        $detectedMime = function_exists('mime_content_type') ? @mime_content_type($absolute) : null;
        $expectedMimePrefixes = ['text/', 'application/json', 'application/octet-stream', 'application/vnd.tcpdump.pcap'];
        $mimeOk = false;
        if ($detectedMime) {
            foreach ($expectedMimePrefixes as $prefix) {
                if (str_starts_with($detectedMime, $prefix)) {
                    $mimeOk = true;
                    break;
                }
            }
        } else {
            $mimeOk = true; // tidak bisa deteksi -> lanjut, sudah ada whitelist ekstensi
        }
        if (!$mimeOk) {
            Storage::disk('local')->delete($stored);
            return back()->withErrors([
                'file' => 'MIME type tidak diharapkan untuk file akuisisi: ' . $detectedMime,
            ]);
        }

        $summary = $this->parser->parse($absolute, $extension);

        $acq = AcquisitionFile::create([
            'experiment_id'    => $experiment->id,
            'original_name'    => $file->getClientOriginalName(),
            'stored_name'      => $stored,
            'extension'        => $extension,
            'size_bytes'       => $file->getSize(),
            'mime_type'        => $file->getMimeType(),
            'capture_label'    => $data['capture_label'],
            'scenario_key'     => $data['scenario_key'] ?: $experiment->scenario_key,
            'source_ip'        => $data['source_ip'] ?: $experiment->source_ip,
            'target_ip'        => $data['target_ip'] ?: $experiment->target_ip,
            'capture_started_at' => $data['capture_started_at'] ?? null,
            'capture_ended_at' => $data['capture_ended_at'] ?? null,
            'total_packets'    => $summary['total_packets'],
            'tcp_packets'      => $summary['tcp_packets'],
            'http_packets'     => $summary['http_packets'],
            'avg_packet_size'  => $summary['avg_packet_size'],
            'top_source_ips'   => $summary['top_source_ips'],
            'top_destination_ips' => $summary['top_destination_ips'],
            'protocol_distribution' => $summary['protocol_distribution'],
            'total_connections'=> $summary['total_connections'],
            'avg_connection_duration' => $summary['avg_connection_duration'],
            'half_open_connections' => $summary['half_open_connections'],
            'parsed_summary'   => $summary['parsed_summary'],
        ]);

        $experiment->update(['status' => 'data_acquired']);
        $this->audit->log('acquisition.uploaded', $acq, [
            'experiment' => $experiment->experiment_code,
            'extension'  => $extension,
        ]);

        return redirect()->route('experiments.show', $experiment)
            ->with('success', 'File akuisisi berhasil diunggah dan dipreview.');
    }

    public function destroy(AcquisitionFile $acquisition)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        Storage::disk('local')->delete($acquisition->stored_name);
        $this->audit->log('acquisition.deleted', $acquisition);
        $acquisition->delete();

        return back()->with('success', 'File akuisisi dihapus.');
    }
}
