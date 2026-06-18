@extends('layouts.app')

@section('title', 'Laporan · ' . ($report->experiment?->experiment_code ?? '—'))
@section('subtitle', $report->title)

@section('content')

@php
    $features = $report->experiment?->extractedFeature;
    $scoreLabel = fn (?string $category) => \App\Support\AttackPresentation::scoreLabel($category);
    $decisionLabel = fn (?string $decision) => \App\Support\AttackPresentation::decisionLabel($decision);
@endphp

<div class="card mb-4">
    <div class="card-header">
        <p class="card-title">{{ $report->title }}</p>
        <div class="flex gap-2">
            <a href="{{ route('reports.pdf', $report) }}" class="btn-success text-xs"><x-icon name="download" class="w-4 h-4"/> Download PDF</a>
            <a href="{{ route('experiments.features.csv', $report->experiment) }}" class="btn-ghost text-xs">Export Fitur CSV</a>
            <a href="{{ route('ai.export', $report->experiment) }}" class="btn-ghost text-xs">Export AI JSON</a>
        </div>
    </div>
    <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div><p class="text-xs text-slate-500">Eksperimen</p><p class="font-mono text-cyan-300">{{ $report->experiment?->experiment_code }}</p></div>
        <div>
            <p class="text-xs text-slate-500">Keputusan (gated)</p>
            <p class="text-slate-100 font-semibold">{{ $decisionLabel($report->final_decision) }}</p>
            @if (!empty($report->voting_summary['voting_decision']))
                <p class="text-[11px] text-slate-500 mt-1">Voting AI: {{ $report->voting_summary['voting_decision'] }}</p>
            @endif
        </div>
        <div><p class="text-xs text-slate-500">Confidence Rata-rata Jawaban AI</p><p class="text-amber-300 font-mono">{{ $report->voting_average_confidence }}%</p>
        <p class="text-[11px] text-slate-500 mt-1">Bukan confidence serangan</p></div>
    </div>
</div>

<div class="rounded-lg p-4 bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs mb-4">
    <strong>Disclaimer:</strong>
    Klasifikasi pada laporan ini berasal dari evidence-gated scoring (Wireshark + Snort + pola koneksi).
    Label <em>Attack Detected</em> hanya muncul ketika bukti gabungan terpenuhi.
    Eksperimen dijalankan pada VM lab lokal (subnet 192.168.56.0/24) dan tidak boleh digeneralisasi ke target publik.
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach ([
        'Tujuan' => $report->purpose,
        'Topologi' => $report->topology,
        'Tools yang Digunakan' => $report->tools_used,
        'Kesimpulan' => $report->conclusion,
        'Keterbatasan' => $report->limitations,
        'Rekomendasi' => $report->recommendations,
    ] as $title => $content)
        <div class="card">
            <div class="card-header"><p class="card-title">{{ $title }}</p></div>
            <div class="p-5 text-sm text-slate-300 whitespace-pre-line">{{ $content ?: '—' }}</div>
        </div>
    @endforeach

    @if ($features)
        <div class="card lg:col-span-2">
            <div class="card-header"><p class="card-title">Skor Radar & Hasil AI</p></div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1"><canvas id="reportRadar" height="240"></canvas></div>
                <div class="md:col-span-2 grid grid-cols-2 gap-2 text-xs">
                    @foreach ($features->radarScores() as $key => $value)
                        <div class="flex justify-between border-b border-slate-800 pb-1">
                            <span class="text-slate-400">{{ str_replace('_',' ',$key) }}</span>
                            <span class="font-mono text-cyan-300">{{ round($value,1) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between border-b border-slate-800 pb-1 col-span-2 text-amber-300">
                        <span class="text-slate-300">Final Attack Score</span>
                        <span class="font-mono">{{ $features->final_attack_score }} ({{ $scoreLabel($features->attack_category) }})</span>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radar = @json($features->radarScores());
            new Chart(document.getElementById('reportRadar'), {
                type: 'radar',
                data: { labels: Object.keys(radar).map(k=>k.replaceAll('_',' ')),
                    datasets: [{ data: Object.values(radar),
                        backgroundColor:'rgba(34,211,238,0.18)', borderColor:'rgba(34,211,238,0.9)',
                        pointBackgroundColor:'#22d3ee' }]},
                options: { plugins:{legend:{display:false}},
                    scales:{r:{min:0,max:100,grid:{color:'#334155'},angleLines:{color:'#334155'},
                        pointLabels:{color:'#94a3b8',font:{size:10}}, ticks:{display:false}}}}
            });
        });
        </script>
    @endif
</div>

@endsection
