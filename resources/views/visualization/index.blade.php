@extends('layouts.app')

@section('title', 'Visualisasi Eksperimen')
@section('subtitle', 'Grafik interaktif untuk timeline, severity, protocol, koneksi aktif, heatmap, dan radar.')

@section('content')

@php
    $scoreTone = fn (?string $category) => match ($category) {
        'Normal' => 'score-tone-normal',
        'Suspicious' => 'score-tone-suspicious',
        'Possible Slowloris' => 'score-tone-possible',
        'Strong Slowloris Indication' => 'score-tone-strong',
        default => 'score-tone-neutral',
    };
@endphp

<div class="card mb-4">
    <form method="GET" class="card-header gap-3 flex-wrap">
        <p class="card-title">Pilih Eksperimen</p>
        <div class="flex items-center gap-2">
            <select name="exp" class="input-field" onchange="window.location.href='{{ url('visualization') }}/' + this.value">
                @foreach ($experiments as $e)
                    <option value="{{ $e->id }}" @selected($selected && $selected->id===$e->id)>{{ $e->experiment_code }} — {{ $e->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

@if (!$selected || !$datasets)
    <div class="card p-8 text-center text-slate-500">
        Belum ada eksperimen yang dapat divisualisasikan.
    </div>
@else
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="card xl:col-span-1">
        <div class="card-header"><p class="card-title">Skor Indikator (Radar)</p></div>
        <div class="p-5">
            <canvas id="radarChart" height="320"></canvas>
            @if (!is_null($datasets['final_score']))
                <div class="mt-4 score-result-card {{ $scoreTone($datasets['attack_category']) }}">
                    <p class="score-result-caption">Final Attack Score</p>
                    <p class="score-result-value">{{ $datasets['final_score'] }}</p>
                    <p class="score-result-category">{{ $datasets['attack_category'] }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="card xl:col-span-2">
        <div class="card-header"><p class="card-title">Timeline Packet Per Second</p></div>
        <div class="p-5"><canvas id="timelineChart" height="200"></canvas></div>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Distribusi Protokol</p></div>
        <div class="p-5"><canvas id="protocolChart" height="240"></canvas></div>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Severity Snort Alerts</p></div>
        <div class="p-5"><canvas id="severityChart" height="240"></canvas></div>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Koneksi Aktif Terhadap Waktu</p></div>
        <div class="p-5"><canvas id="connectionsChart" height="240"></canvas></div>
    </div>

    <div class="card xl:col-span-3">
        <div class="card-header"><p class="card-title">Heatmap Source IP × Destination Port</p></div>
        <div class="p-5">
            @if (count($datasets['heatmap']) === 0)
                <p class="text-sm text-slate-500">Belum ada data heatmap.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-slate-400 text-left">Source IP</th>
                                <th class="px-3 py-2 text-slate-400 text-left">Destination Port</th>
                                <th class="px-3 py-2 text-slate-400 text-left">Total Hit</th>
                                <th class="px-3 py-2 text-slate-400 text-left">Heat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $max = collect($datasets['heatmap'])->max('value') ?: 1; @endphp
                            @foreach ($datasets['heatmap'] as $h)
                                @php $intensity = round(($h['value']/$max) * 100); @endphp
                                <tr>
                                    <td class="px-3 py-1.5 font-mono text-slate-300">{{ $h['source_ip'] }}</td>
                                    <td class="px-3 py-1.5 font-mono text-slate-300">{{ $h['port'] }}</td>
                                    <td class="px-3 py-1.5 font-mono text-cyan-300">{{ $h['value'] }}</td>
                                    <td class="px-3 py-1.5">
                                        <div class="h-2 rounded-full bg-slate-800 w-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-cyan-500 to-rose-500" style="width: {{ $intensity }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radar = @json($datasets['radar']);
    const severity = @json($datasets['severity']);
    const protocol = @json($datasets['protocol']);
    const timeline = @json($datasets['timeline']);
    const connections = @json($datasets['connections_over_time']);

    const baseGrid = '#1e293b';
    const baseColor = '#94a3b8';

    const chartDefaults = (extra = {}) => ({
        responsive: true,
        plugins: { legend: { labels: { color: baseColor } } },
        scales: {
            x: { grid: { color: baseGrid }, ticks: { color: baseColor } },
            y: { grid: { color: baseGrid }, ticks: { color: baseColor }, beginAtZero: true }
        },
        ...extra,
    });

    if (radar && Object.keys(radar).length) {
        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: Object.keys(radar).map(k => k.replaceAll('_', ' ')),
                datasets: [{
                    label: 'Skor',
                    data: Object.values(radar),
                    backgroundColor: 'rgba(34,211,238,0.18)',
                    borderColor: 'rgba(34,211,238,0.9)',
                    pointBackgroundColor: '#22d3ee',
                }]
            },
            options: {
                scales: { r: { min: 0, max: 100,
                    angleLines: { color: baseGrid }, grid: { color: baseGrid },
                    pointLabels: { color: baseColor, font: { size: 10 } },
                    ticks: { display: false, stepSize: 25 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    new Chart(document.getElementById('timelineChart'), {
        type: 'line',
        data: {
            labels: timeline.map(t => t.time + 's'),
            datasets: [{
                label: 'Packet / Second',
                data: timeline.map(t => t.pps),
                borderColor: '#22d3ee',
                backgroundColor: 'rgba(34,211,238,0.15)',
                tension: 0.3,
                fill: true,
            }]
        },
        options: chartDefaults()
    });

    new Chart(document.getElementById('protocolChart'), {
        type: 'pie',
        data: {
            labels: Object.keys(protocol),
            datasets: [{
                data: Object.values(protocol),
                backgroundColor: ['#22d3ee','#10b981','#f59e0b','#ef4444','#6366f1','#a855f7'],
            }]
        },
        options: { plugins: { legend: { labels: { color: baseColor } } } }
    });

    new Chart(document.getElementById('severityChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(severity).length ? Object.keys(severity) : ['high','medium','low'],
            datasets: [{
                label: 'Total Alert',
                data: Object.keys(severity).length
                    ? Object.values(severity)
                    : [0,0,0],
                backgroundColor: ['#ef4444','#f59e0b','#64748b'],
            }]
        },
        options: chartDefaults()
    });

    new Chart(document.getElementById('connectionsChart'), {
        type: 'line',
        data: {
            labels: connections.map(c => 't' + c.time),
            datasets: [{
                label: 'Active Connections',
                data: connections.map(c => c.active),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.18)',
                tension: 0.4,
                fill: true,
            }]
        },
        options: chartDefaults()
    });
});
</script>
@endif

@endsection
