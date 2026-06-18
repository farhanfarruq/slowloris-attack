@extends('layouts.app')

@section('title', 'AI Analysis')
@section('subtitle', 'Bandingkan analisis multi-model AI dengan skoring logic program per tool profile.')

@section('content')

@php
    $scoreTone = fn (?string $category) => \App\Support\AttackPresentation::scoreTone($category);
    $scoreLabel = fn (?string $category) => \App\Support\AttackPresentation::scoreLabel($category);
@endphp

<div class="card mb-4">
    <div class="card-header"><p class="card-title">Provider Tersedia</p>
        @auth @if(auth()->user()->isAdmin())
            <a href="{{ route('settings.api') }}" class="text-xs text-cyan-300">Pengaturan API →</a>
        @endif @endauth
    </div>
    <div class="p-5 grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
        @foreach ($providers as $p)
            <div class="rounded-lg border border-slate-800/70 p-3 bg-slate-950/60">
                <p class="text-slate-200 font-medium">{{ $p['label'] }}</p>
                <p class="text-[11px] text-slate-500">{{ $p['driver'] }} · {{ $p['model'] ?? '—' }} @if($p['tool_profile']) · {{ strtoupper($p['tool_profile']) }} @endif</p>
                <p class="mt-1.5">
                    @if ($p['can_run'])
                        <span class="badge-emerald">Siap</span>
                    @elseif (!$p['use_live_api'])
                        <span class="badge-slate">Live API off</span>
                    @else
                        <span class="badge-amber">API key kosong</span>
                    @endif
                </p>
            </div>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header">
        <p class="card-title">Eksperimen</p>
        <form method="GET" class="flex items-center gap-2">
            <select name="tool_profile" class="input-field max-w-xs">
                <option value="">Semua tool profile</option>
                @foreach ($toolProfiles as $profile)
                    <option value="{{ $profile['key'] }}" @selected(request('tool_profile')===$profile['key'])>{{ $profile['label'] }}</option>
                @endforeach
            </select>
            <button class="btn-primary text-xs">Filter</button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead><tr><th>Kode</th><th>Nama</th><th>Tool Profile</th><th>Skor Logic</th><th>Jumlah Model AI</th><th>Avg Confidence</th><th></th></tr></thead>
            <tbody>
                @forelse ($experiments as $exp)
                    <tr>
                        <td class="font-mono text-cyan-300">{{ $exp->experiment_code }}</td>
                        <td>{{ $exp->name }}</td>
                        <td>
                            <span class="badge-slate">{{ strtoupper($exp->tool_profile ?? 'slowloris') }}</span>
                            @if($exp->attack_pattern)<p class="font-mono text-[11px] text-slate-500 mt-1">{{ $exp->attack_pattern }}</p>@endif
                        </td>
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
                        <td class="font-mono">{{ $exp->aiResults->count() }}</td>
                        <td class="font-mono">{{ round($exp->aiResults->avg('confidence_score') ?? 0, 1) }}%</td>
                        <td class="text-right">
                            <a href="{{ route('ai.show', $exp) }}" class="btn-primary text-xs">AI Analysis</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">Belum ada eksperimen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $experiments->links() }}</div>
</div>

@endsection
