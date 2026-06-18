@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan eksperimen, akuisisi, validasi, dan hasil AI.')

@section('content')

@php
    $statusBadge = [
        'normal'          => ['Traffic Normal',         'badge-emerald', 'status-summary-card--normal'],
        'suspicious'      => ['Suspicious',             'badge-amber', 'status-summary-card--suspicious'],
        'attack_detected' => ['Attack Detected',        'badge-rose', 'status-summary-card--attack'],
        'inconclusive'    => ['Inconclusive',           'badge-slate', 'status-summary-card--inconclusive'],
        'pending'         => ['Belum Dianalisis',       'badge-slate', 'status-summary-card--pending'],
    ];
    $scoreTone = fn (?string $category) => \App\Support\AttackPresentation::scoreTone($category);
    $scoreLabel = fn (?string $category) => \App\Support\AttackPresentation::scoreLabel($category);
    $trafficLabels = [
        'normal' => 'normal',
        'slowloris_lab' => 'attack lab',
        'mixed' => 'mixed',
        'unknown' => 'unknown',
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
    <div class="stat-card xl:col-span-1">
        <p class="text-xs text-slate-400 uppercase tracking-wider">File Akuisisi</p>
        <p class="mt-2 text-3xl font-semibold text-cyan-300">{{ number_format($stats['total_acquisition']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Diunggah dari Wireshark/dumpcap</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">File Validasi</p>
        <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ number_format($stats['total_validation']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Hasil ekspor Snort</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Alert Snort</p>
        <p class="mt-2 text-3xl font-semibold text-amber-300">{{ number_format($stats['total_alerts']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Total alert tervalidasi</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Koneksi Mencurigakan</p>
        <p class="mt-2 text-3xl font-semibold text-rose-300">{{ number_format($stats['suspicious_conn']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Long-lived / half-open</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Confidence Jawaban AI (Semua Kelas)</p>
        <p class="mt-2 text-3xl font-semibold text-sky-300">{{ $stats['avg_ai_confidence_all'] }}<span class="text-base">%</span></p>
        <p class="text-xs text-slate-500 mt-1">Bukan confidence serangan. Sub-skor di bawah hanya untuk model yang mendeteksi attack: <span class="text-rose-300 font-mono">{{ $stats['avg_ai_confidence_attack'] }}%</span></p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Total Eksperimen</p>
        <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($stats['experiment_total']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Termasuk baseline & uji</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
    <div class="card lg:col-span-2">
        <div class="card-header">
            <p class="card-title">Status Eksperimen</p>
            <a href="{{ route('experiments.index') }}" class="text-xs text-cyan-300 hover:text-cyan-200">Lihat semua →</a>
        </div>
        <div class="p-5 grid grid-cols-2 sm:grid-cols-5 gap-3">
            @foreach (['normal','suspicious','attack_detected','inconclusive','pending'] as $key)
                @php $count = $stats['experiment_status'][$key] ?? 0; @endphp
                <div class="status-summary-card {{ $statusBadge[$key][2] }}">
                    <p class="status-summary-label">{{ $statusBadge[$key][0] }}</p>
                    <p class="status-summary-value">{{ $count }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <p class="card-title">Keputusan Final</p>
        </div>
        <div class="p-5 space-y-3">
            @forelse ($finalDecisionSummary as $decision => $count)
                @php
                    $decisionClass = match ($decision) {
                        'Serangan asli' => 'decision-summary-row--attack',
                        'Traffic normal' => 'decision-summary-row--normal',
                        'Perlu validasi lanjutan' => 'decision-summary-row--review',
                        default => 'decision-summary-row--neutral',
                    };
                @endphp
                <div class="decision-summary-row {{ $decisionClass }}">
                    <div class="flex items-center gap-2">
                        <span class="decision-summary-dot"></span>
                        <span class="decision-summary-label">{{ $decision }}</span>
                    </div>
                    <span class="decision-summary-value">{{ $count }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada keputusan final.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <p class="card-title">Eksperimen Terbaru</p>
        <div class="flex items-center gap-3">
            <a href="{{ route('experiments.index') }}" class="text-xs text-cyan-300 hover:text-cyan-200">Lihat semua</a>
            <a href="{{ route('experiments.create') }}" class="text-xs text-cyan-300 hover:text-cyan-200">+ Buat eksperimen</a>
        </div>
    </div>
    <div class="max-h-[760px] overflow-auto">
        <table class="table-stripe">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Tanggal</th>
                    <th>Tipe Traffic</th>
                    <th>Skor Akhir</th>
                    <th>Status</th>
                    <th>Confidence Jawaban AI</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentExperiments as $exp)
                    <tr class="hover:bg-slate-800/40">
                        <td class="font-mono text-cyan-300">{{ $exp->experiment_code }}</td>
                        <td class="text-slate-200">{{ $exp->name }}</td>
                        <td class="text-slate-400">{{ $exp->experiment_date?->format('d M Y') }}</td>
                        <td><span class="badge-cyan">{{ $trafficLabels[$exp->traffic_type] ?? str_replace('_', ' ', $exp->traffic_type) }}</span></td>
                        <td>
                            @if ($exp->extractedFeature)
                                <span class="score-pill {{ $scoreTone($exp->extractedFeature->attack_category) }}">
                                    <span class="score-pill-value">{{ $exp->extractedFeature->final_attack_score }}</span>
                                    <span class="score-pill-label">{{ $scoreLabel($exp->extractedFeature->attack_category) }}</span>
                                </span>
                            @else
                                <span class="text-xs text-slate-500">—</span>
                            @endif
                        </td>
                        <td><span class="{{ $statusBadge[$exp->experiment_status][1] ?? 'badge-slate' }}">{{ $statusBadge[$exp->experiment_status][0] ?? '—' }}</span></td>
                        <td class="text-slate-400">{{ round($exp->aiResults->avg('confidence_score') ?? 0, 1) }}%</td>
                        <td class="text-right">
                            <a href="{{ route('experiments.show', $exp) }}" class="text-xs text-cyan-300 hover:text-cyan-200">Detail →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-8 text-slate-500">Belum ada eksperimen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
