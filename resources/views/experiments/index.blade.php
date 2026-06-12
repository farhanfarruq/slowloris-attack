@extends('layouts.app')

@section('title', 'Dataset Eksperimen')
@section('subtitle', 'Daftar lengkap eksperimen, file, status, dan hasil AI.')

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

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex items-center gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari kode atau nama eksperimen..." class="input-field max-w-sm">
            <select name="traffic_type" class="input-field max-w-xs">
                <option value="">Semua tipe traffic</option>
                @foreach (['normal','slowloris_lab','mixed','unknown'] as $t)
                    <option value="{{ $t }}" @selected(request('traffic_type')===$t)>{{ str_replace('_',' ',$t) }}</option>
                @endforeach
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
                    <th>Skenario</th>
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
                        <td><span class="badge-cyan">{{ str_replace('_', ' ', $exp->traffic_type) }}</span></td>
                        <td class="font-mono text-xs text-slate-300">{{ $exp->scenario_key ?? '—' }}</td>
                        <td class="text-slate-300">{{ $exp->acquisitionFiles->count() }}</td>
                        <td class="text-slate-300">{{ $exp->validationFiles->count() }}</td>
                        <td>
                            @if ($exp->extractedFeature)
                                <span class="score-pill {{ $scoreTone($exp->extractedFeature->attack_category) }}">
                                    <span class="score-pill-value">{{ $exp->extractedFeature->final_attack_score }}</span>
                                    <span class="score-pill-label">{{ $exp->extractedFeature->attack_category }}</span>
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
                    <tr><td colspan="10" class="text-center py-8 text-slate-500">Belum ada eksperimen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3">
        {{ $experiments->links() }}
    </div>
</div>

@endsection
