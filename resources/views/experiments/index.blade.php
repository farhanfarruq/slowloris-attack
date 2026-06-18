@extends('layouts.app')

@section('title', 'Dataset Eksperimen')
@section('subtitle', 'Daftar lengkap eksperimen, file, status, dan hasil AI.')

@section('content')

@php
    $scoreTone = fn (?string $category) => \App\Support\AttackPresentation::scoreTone($category);
    $scoreLabel = fn (?string $category) => \App\Support\AttackPresentation::scoreLabel($category);
    $trafficLabels = [
        'normal' => 'normal',
        'slowloris_lab' => 'attack lab',
        'mixed' => 'mixed',
        'unknown' => 'unknown',
    ];
@endphp

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex items-center gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari kode atau nama eksperimen..." class="input-field max-w-sm">
            <select name="traffic_type" class="input-field max-w-xs">
                <option value="">Semua tipe traffic</option>
                @foreach (['normal','slowloris_lab','mixed','unknown'] as $t)
                    <option value="{{ $t }}" @selected(request('traffic_type')===$t)>{{ $trafficLabels[$t] ?? str_replace('_',' ',$t) }}</option>
                @endforeach
            </select>
            <select name="tool_profile" class="input-field max-w-xs">
                <option value="">Semua tool profile</option>
                @foreach ($toolProfiles as $profile)
                    <option value="{{ $profile['key'] }}" @selected(request('tool_profile')===$profile['key'])>
                        {{ $profile['label'] }}
                    </option>
                @endforeach
            </select>
            <select name="target_platform" class="input-field max-w-xs">
                <option value="">Semua target</option>
                <option value="vm_ubuntu_server" @selected(request('target_platform')==='vm_ubuntu_server')>VM Ubuntu Server</option>
            </select>
            <button type="submit" class="btn-primary">Cari</button>
        </form>
        @auth
            @if (auth()->user()->isAdmin())
                <a href="{{ route('experiments.create') }}" class="btn-primary"><x-icon name="plus" class="w-4 h-4"/> Eksperimen Baru</a>
            @endif
        @endauth
    </div>

    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Tool Profile</th>
                    <th>Skenario</th>
                    <th>Target</th>
                    <th>Akuisisi</th>
                    <th>Validasi</th>
                    <th>Skor</th>
                    <th>Hasil AI</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($experiments as $exp)
                    <tr class="hover:bg-slate-800/40">
                        <td class="font-mono text-cyan-300">{{ $exp->experiment_code }}</td>
                        <td class="text-slate-200">{{ $exp->name }}</td>
                        <td class="text-slate-400">{{ $exp->experiment_date?->format('d M Y') }}</td>
                        <td><span class="badge-cyan">{{ $trafficLabels[$exp->traffic_type] ?? str_replace('_', ' ', $exp->traffic_type) }}</span></td>
                        <td>
                            <span class="badge-slate">{{ strtoupper($exp->tool_profile ?? 'slowloris') }}</span>
                            @if ($exp->attack_pattern)
                                <p class="mt-1 font-mono text-[11px] text-slate-500">{{ $exp->attack_pattern }}</p>
                            @endif
                        </td>
                        <td class="font-mono text-xs text-slate-300">{{ $exp->scenario_key ?? '—' }}</td>
                        <td class="font-mono text-xs text-slate-400">{{ $exp->target_platform ?? '—' }}</td>
                        <td class="text-slate-300">{{ $exp->acquisitionFiles->count() }}</td>
                        <td class="text-slate-300">{{ $exp->validationFiles->count() }}</td>
                        <td>
                            @if ($exp->extractedFeature)
                                <span class="score-pill {{ $scoreTone($exp->extractedFeature->attack_category) }}">
                                    <span class="score-pill-value">{{ $exp->extractedFeature->final_attack_score }}</span>
                                    <span class="score-pill-label">{{ $scoreLabel($exp->extractedFeature->attack_category) }}</span>
                                </span>
                            @else
                                <span class="text-xs text-slate-500">Belum dianalisis</span>
                            @endif
                        </td>
                        <td class="text-slate-300">{{ $exp->aiResults->count() }} model</td>
                        <td class="space-x-1">
                            <a href="{{ route('experiments.show', $exp) }}" class="text-cyan-300 hover:text-cyan-200 text-xs">Detail</a>
                            @auth
                                @if (auth()->user()->isAdmin())
                                    | <a href="{{ route('reports.create', $exp) }}" class="text-emerald-300 hover:text-emerald-200 text-xs">Laporan</a>
                                    |
                                    <form action="{{ route('experiments.destroy', $exp) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus eksperimen?');">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-300 hover:text-rose-200 text-xs">Hapus</button>
                                    </form>
                                @endif
                            @endauth
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center py-8 text-slate-500">Belum ada eksperimen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3">
        {{ $experiments->links() }}
    </div>
</div>

@endsection
