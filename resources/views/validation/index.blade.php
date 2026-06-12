@extends('layouts.app')

@section('title', 'Upload Data Validasi Snort')
@section('subtitle', 'Unggah file alert Snort dari mode IDS atau IPS (.json, .log, .csv, .txt).')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card lg:col-span-1">
        <div class="card-header"><p class="card-title">Form Upload Validasi</p></div>
        @auth
            @if (auth()->user()->isAdmin())
                <form action="{{ route('validation.store') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="label-field">Pilih Eksperimen *</label>
                        <select name="experiment_id" id="experiment_id" class="input-field" required>
                            @foreach ($experiments as $e)
                                <option value="{{ $e->id }}" @selected(request('exp')==$e->id)>{{ $e->experiment_code }} — {{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-field">Pasangkan File Akuisisi *</label>
                        <select name="acquisition_file_id" id="acquisition_file_id" class="input-field" required>
                            @foreach ($experiments as $e)
                                @foreach ($e->acquisitionFiles as $acq)
                                    <option value="{{ $acq->id }}" data-experiment="{{ $e->id }}" @selected(old('acquisition_file_id') == $acq->id)>
                                        {{ $e->experiment_code }} · {{ $acq->capture_label ?? 'tanpa-label' }} · {{ $acq->original_name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">Validasi Snort harus dipasangkan ke PCAP/akuisisi yang sama.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label-field">Mode Snort *</label>
                            <select name="snort_mode" class="input-field" required>
                                <option value="ids">IDS</option>
                                <option value="ips">IPS</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-field">Threshold Alert</label>
                            <input type="number" name="threshold" min="0" max="100000" class="input-field" placeholder="10">
                        </div>
                    </div>
                    <div>
                        <label class="label-field">Rule Set</label>
                        <input type="text" name="rule_set" class="input-field" placeholder="community + custom">
                    </div>
                    <div>
                        <label class="label-field">Interface Monitoring</label>
                        <input type="text" name="monitoring_interface" class="input-field" placeholder="enp0s8">
                    </div>
                    <div>
                        <label class="label-field">Catatan Validasi</label>
                        <textarea name="notes" rows="2" class="input-field"></textarea>
                    </div>
                    <div>
                        <label class="label-field">File Alert *</label>
                        <input type="file" name="file" required class="input-field" accept=".json,.log,.csv,.txt">
                        <p class="text-[11px] text-slate-500 mt-1">Diizinkan: .json, .log, .csv, .txt (maks {{ config('upload.max_size_mb') }} MB).</p>
                    </div>
                    <button class="btn-primary w-full justify-center"><x-icon name="shield" class="w-4 h-4"/> Upload Data Validasi</button>
                </form>
            @else
                <div class="p-5 text-sm text-slate-500">Hanya peneliti yang dapat mengunggah.</div>
            @endif
        @endauth
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Riwayat Validasi & Preview</p></div>
        <div class="overflow-x-auto">
            <table class="table-stripe">
                <thead>
                    <tr><th>File</th><th>Pasangan</th><th>Eksperimen</th><th>Mode</th><th>Total Alert</th><th>Severity</th>
                        <th>Slow HTTP?</th><th>Top Src</th><th>Top Port</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($files as $f)
                        @php
                            $topSrc = collect($f->top_source_ips ?? [])->keys()->first();
                            $topPort = collect($f->top_destination_ports ?? [])->keys()->first();
                        @endphp
                        <tr>
                            <td class="text-slate-200">{{ $f->original_name }}<p class="text-[11px] text-slate-500">{{ $f->extension }} · {{ round($f->size_bytes/1024,1) }} KB · {{ $f->created_at->format('d M H:i') }}</p></td>
                            <td class="text-xs text-slate-300">
                                <span class="font-mono">{{ $f->capture_label ?? '—' }}</span>
                                <p class="text-[11px] text-slate-500">{{ $f->acquisitionFile?->original_name ?? 'akuisisi tidak ada' }}</p>
                            </td>
                            <td><a class="text-cyan-300" href="{{ route('experiments.show', $f->experiment) }}">{{ $f->experiment->experiment_code }}</a></td>
                            <td><span class="badge-cyan">{{ strtoupper($f->snort_mode) }}</span></td>
                            <td class="font-mono">{{ number_format($f->total_alerts ?? 0) }}</td>
                            <td class="text-slate-300">{{ ucfirst($f->highest_severity ?? '—') }}</td>
                            <td>
                                @if ($f->matches_slow_http_pattern)
                                    <span class="badge-rose">Match</span>
                                @else
                                    <span class="badge-slate">No</span>
                                @endif
                            </td>
                            <td class="font-mono text-xs">{{ $topSrc ?? '—' }}</td>
                            <td class="font-mono text-xs">{{ $topPort ?? '—' }}</td>
                            <td class="text-right">
                                @auth @if (auth()->user()->isAdmin())
                                    <form action="{{ route('validation.destroy', $f) }}" method="POST" onsubmit="return confirm('Hapus?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-300 text-xs">Hapus</button>
                                    </form>
                                @endif @endauth
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-6 text-slate-500">Belum ada file validasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $files->links() }}</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const experiment = document.getElementById('experiment_id');
        const acquisition = document.getElementById('acquisition_file_id');

        if (!experiment || !acquisition) return;

        const options = Array.from(acquisition.options);
        const syncAcquisitionOptions = function () {
            const selectedExperiment = experiment.value;
            let firstVisible = null;

            options.forEach(function (option) {
                const visible = option.dataset.experiment === selectedExperiment;
                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && !firstVisible) firstVisible = option;
            });

            if (acquisition.selectedOptions.length && acquisition.selectedOptions[0].disabled && firstVisible) {
                acquisition.value = firstVisible.value;
            }
        };

        experiment.addEventListener('change', syncAcquisitionOptions);
        syncAcquisitionOptions();
    });
</script>

@endsection
