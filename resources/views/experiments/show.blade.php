@extends('layouts.app')

@section('title', 'Detail ' . $experiment->experiment_code)
@section('subtitle', $experiment->name)

@section('content')

@php
    $features = $experiment->extractedFeature;
    $radar = $features?->radarScores() ?? [];
    $statusColor = [
        'normal' => 'emerald', 'suspicious' => 'amber',
        'attack_detected' => 'rose', 'inconclusive' => 'slate', 'pending' => 'slate',
    ][$experiment->experiment_status] ?? 'slate';
    $scoreTone = fn (?string $category) => match ($category) {
        'Normal' => 'score-tone-normal',
        'Suspicious' => 'score-tone-suspicious',
        'Possible Slowloris' => 'score-tone-possible',
        'Strong Slowloris Indication' => 'score-tone-strong',
        default => 'score-tone-neutral',
    };
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="card xl:col-span-2">
        <div class="card-header">
            <p class="card-title">Metadata Eksperimen</p>
            @auth
                @if (auth()->user()->isAdmin())
                    <div class="flex items-center gap-2">
                        <a href="{{ route('experiments.edit', $experiment) }}" class="btn-ghost text-xs">Edit</a>
                        <a href="{{ route('reports.create', $experiment) }}" class="btn-success text-xs">Generate Laporan</a>
                    </div>
                @endif
            @endauth
        </div>
        <div class="p-5 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-xs text-slate-500">Kode</p>
                <p class="font-mono text-cyan-300 font-semibold">{{ $experiment->experiment_code }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Tanggal</p>
                <p class="text-slate-200">{{ $experiment->experiment_date?->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Tipe Traffic</p>
                <p class="text-slate-200">{{ str_replace('_',' ',$experiment->traffic_type) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Network Interface</p>
                <p class="text-slate-200">{{ $experiment->network_interface ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">IP Target</p>
                <p class="text-slate-200 font-mono">{{ $experiment->target_ip ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">IP Sumber</p>
                <p class="text-slate-200 font-mono">{{ $experiment->source_ip ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Durasi</p>
                <p class="text-slate-200">{{ $experiment->capture_duration }} dtk</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Ground Truth</p>
                <p class="text-slate-200">{{ $experiment->ground_truth_label ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Status</p>
                <span class="badge bg-{{ $statusColor }}-500/15 text-{{ $statusColor }}-300 border-{{ $statusColor }}-500/30">
                    {{ str_replace('_',' ',$experiment->experiment_status) }}
                </span>
            </div>
            <div class="md:col-span-3">
                <p class="text-xs text-slate-500">Catatan</p>
                <p class="text-slate-300 whitespace-pre-line">{{ $experiment->notes ?: '—' }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Skor Indikator</p></div>
        <div class="p-5">
            @if (!empty($radar))
                <canvas id="radarChart" height="280"></canvas>
                <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                    @foreach ($radar as $key => $value)
                        <div class="flex justify-between border-b border-slate-800 pb-1">
                            <span class="text-slate-400">{{ str_replace('_', ' ', $key) }}</span>
                            <span class="font-mono text-cyan-300">{{ round($value, 1) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 score-result-card {{ $scoreTone($features->attack_category) }}">
                    <p class="score-result-caption">Final Attack Score</p>
                    <p class="score-result-value">{{ $features->final_attack_score }}</p>
                    <p class="score-result-category">{{ $features->attack_category }}</p>
                </div>
                <p class="mt-3 text-[11px] text-slate-500 leading-relaxed">
                    <strong>Status eksperimen:</strong>
                    <span class="text-slate-300">{{ str_replace('_',' ',$experiment->experiment_status) }}</span>.
                    Kategori "Possible Slowloris" dipetakan ke <code>suspicious</code>.
                    Hanya "Strong Slowloris Indication" yang lulus gate evidence
                    (Snort relevan + koneksi long-lived + low-bw·high-conn) yang dipetakan ke
                    <code>attack_detected</code> / "Serangan asli".
                </p>
            @else
                <p class="text-sm text-slate-500">Belum ada hasil ekstraksi fitur. Jalankan analisis terlebih dahulu.</p>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
    <div class="card">
        <div class="card-header">
            <p class="card-title">File Akuisisi ({{ $experiment->acquisitionFiles->count() }})</p>
            <a href="{{ route('acquisition.index') }}?exp={{ $experiment->id }}" class="text-xs text-cyan-300">+ Upload</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-stripe">
                <thead><tr><th>File</th><th>Label</th><th>Pkt</th><th>TCP</th><th>HTTP</th><th>Conn</th><th></th></tr></thead>
                <tbody>
                    @forelse ($experiment->acquisitionFiles as $f)
                        <tr>
                            <td class="text-slate-200">{{ $f->original_name }}<p class="text-[11px] text-slate-500">{{ $f->extension }} · {{ round($f->size_bytes/1024,1) }} KB</p></td>
                            <td class="text-xs text-slate-300">
                                <span class="font-mono">{{ $f->capture_label ?? '—' }}</span>
                                <p class="text-[11px] text-slate-500">{{ $f->scenario_key ?? 'no-scenario' }}</p>
                            </td>
                            <td class="font-mono text-cyan-300">{{ number_format($f->total_packets ?? 0) }}</td>
                            <td class="font-mono">{{ number_format($f->tcp_packets ?? 0) }}</td>
                            <td class="font-mono">{{ number_format($f->http_packets ?? 0) }}</td>
                            <td class="font-mono">{{ number_format($f->total_connections ?? 0) }}</td>
                            <td class="text-right">
                                @auth @if (auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('acquisition.destroy', $f) }}" onsubmit="return confirm('Hapus file?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-300 text-xs">Hapus</button>
                                    </form>
                                @endif @endauth
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-6 text-slate-500">Belum ada file akuisisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <p class="card-title">File Validasi Snort ({{ $experiment->validationFiles->count() }})</p>
            <a href="{{ route('validation.index') }}?exp={{ $experiment->id }}" class="text-xs text-cyan-300">+ Upload</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-stripe">
                <thead><tr><th>File</th><th>Pasangan</th><th>Mode</th><th>Total</th><th>Severity</th><th>Slow HTTP?</th><th></th></tr></thead>
                <tbody>
                    @forelse ($experiment->validationFiles as $f)
                        <tr>
                            <td class="text-slate-200">{{ $f->original_name }}<p class="text-[11px] text-slate-500">{{ $f->extension }} · {{ round($f->size_bytes/1024,1) }} KB</p></td>
                            <td class="text-xs text-slate-300">
                                <span class="font-mono">{{ $f->capture_label ?? '—' }}</span>
                                <p class="text-[11px] text-slate-500">{{ $f->acquisitionFile?->original_name ?? 'akuisisi tidak ada' }}</p>
                            </td>
                            <td><span class="badge-cyan">{{ strtoupper($f->snort_mode) }}</span></td>
                            <td class="font-mono">{{ $f->total_alerts }}</td>
                            <td class="text-slate-300">{{ $f->highest_severity }}</td>
                            <td>
                                @if ($f->matches_slow_http_pattern)
                                    <span class="badge-rose">Match</span>
                                @else
                                    <span class="badge-slate">No</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @auth @if (auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('validation.destroy', $f) }}" onsubmit="return confirm('Hapus file?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-300 text-xs">Hapus</button>
                                    </form>
                                @endif @endauth
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-6 text-slate-500">Belum ada file validasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <p class="card-title">Hasil Validasi AI</p>
        <div class="flex gap-2">
            <a href="{{ route('ai.show', $experiment) }}" class="text-xs text-cyan-300 hover:text-cyan-200">Jalankan AI →</a>
            <a href="{{ route('ai.export', $experiment) }}" class="text-xs text-emerald-300 hover:text-emerald-200">Export JSON</a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead><tr><th>Model</th><th>Klasifikasi</th><th>Confidence</th><th>Reason</th><th>Indikator</th></tr></thead>
            <tbody>
                @forelse ($experiment->aiResults as $r)
                    <tr>
                        <td class="text-slate-200">{{ $r->model_name }}@if($r->is_simulated)<span class="ml-1 badge-slate">sim</span>@endif</td>
                        <td>
                            @php
                                $classColor = [
                                    'Slowloris Detected' => 'rose',
                                    'Suspicious'         => 'amber',
                                    'Normal'             => 'emerald',
                                    'Inconclusive'       => 'slate',
                                ][$r->classification] ?? 'slate';
                            @endphp
                            <span class="badge bg-{{ $classColor }}-500/15 text-{{ $classColor }}-300 border-{{ $classColor }}-500/30">{{ $r->classification }}</span>
                        </td>
                        <td class="font-mono text-cyan-300">{{ $r->confidence_score }}%</td>
                        <td class="text-slate-300 max-w-md">{{ $r->reason }}</td>
                        <td class="text-xs text-slate-400">
                            @foreach (array_slice((array)$r->supporting_indicators, 0, 3) as $ind)
                                <span class="block">• {{ $ind }}</span>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-slate-500">Belum ada hasil AI.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
    <div class="card">
        <div class="card-header"><p class="card-title">Catatan</p></div>
        <div class="p-5 space-y-3">
            @forelse ($experiment->reviewerNotes as $n)
                <div class="p-3 rounded-lg bg-slate-950/60 border border-slate-800">
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="text-cyan-300">{{ $n->user->name }} <span class="text-slate-500">· {{ $n->user->isAdmin() ? 'Admin' : 'Viewer' }}</span></span>
                        <span class="text-slate-500">{{ $n->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-slate-200 whitespace-pre-line">{{ $n->note }}</p>
                    @auth @if (auth()->id() === $n->user_id || auth()->user()->isAdmin())
                        <form action="{{ route('reviewer-notes.destroy', $n) }}" method="POST" class="text-right" onsubmit="return confirm('Hapus catatan?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-rose-300">hapus</button>
                        </form>
                    @endif @endauth
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada catatan reviewer.</p>
            @endforelse

            <form action="{{ route('reviewer-notes.store', $experiment) }}" method="POST" class="space-y-2">
                @csrf
                <textarea name="note" rows="3" class="input-field" placeholder="Tulis catatan / arahan untuk eksperimen ini..."></textarea>
                <button class="btn-primary text-xs">Tambah Catatan</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Snort Alerts (terbaru)</p></div>
        <div class="overflow-x-auto">
            <table class="table-stripe">
                <thead><tr><th>Waktu</th><th>Severity</th><th>Src</th><th>Dst:Port</th><th>Pesan</th></tr></thead>
                <tbody>
                    @forelse ($experiment->snortAlerts as $a)
                        <tr>
                            <td class="text-xs text-slate-400 font-mono">{{ optional($a->alert_timestamp)->format('H:i:s') }}</td>
                            <td>
                                @php $sc = ['high'=>'rose','medium'=>'amber','low'=>'slate'][$a->severity] ?? 'slate'; @endphp
                                <span class="badge bg-{{ $sc }}-500/15 text-{{ $sc }}-300 border-{{ $sc }}-500/30">{{ $a->severity }}</span>
                            </td>
                            <td class="font-mono">{{ $a->source_ip }}</td>
                            <td class="font-mono">{{ $a->destination_ip }}:{{ $a->destination_port }}</td>
                            <td class="text-slate-300 max-w-xs truncate">{{ $a->message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-6 text-slate-500">Belum ada alert tersimpan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if (!empty($radar))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radar = @json($radar);
        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: Object.keys(radar).map(k => k.replaceAll('_', ' ')),
                datasets: [{
                    label: 'Skor Indikator',
                    data: Object.values(radar),
                    backgroundColor: 'rgba(34,211,238,0.18)',
                    borderColor: 'rgba(34,211,238,0.9)',
                    pointBackgroundColor: '#22d3ee',
                }]
            },
            options: {
                scales: { r: { min: 0, max: 100,
                    angleLines: { color: '#334155' },
                    grid: { color: '#334155' },
                    pointLabels: { color: '#cbd5e1', font: { size: 10 } },
                    ticks: { display: false, stepSize: 25 }
                }},
                plugins: { legend: { display: false } }
            }
        });
    });
</script>
@endif

@endsection
