@extends('layouts.app')

@section('title', 'Comparison')
@section('subtitle', 'Perbandingan hasil skoring logic program dengan AI Analysis per tool profile.')

@section('content')
@php
    $scoreLabel = fn (?string $category) => \App\Support\AttackPresentation::scoreLabel($category);
@endphp
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
            <thead><tr><th>Kode</th><th>Nama</th><th>Tool Profile</th><th>Logic Score</th><th>AI Results</th><th></th></tr></thead>
            <tbody>
                @forelse ($experiments as $experiment)
                    <tr>
                        <td class="font-mono text-cyan-300">{{ $experiment->experiment_code }}</td>
                        <td>{{ $experiment->name }}</td>
                        <td><span class="badge-slate">{{ strtoupper($experiment->tool_profile ?? 'slowloris') }}</span><p class="font-mono text-[11px] text-slate-500 mt-1">{{ $experiment->attack_pattern ?? $experiment->scenario_key }}</p></td>
                        <td class="font-mono text-cyan-300">{{ $experiment->extractedFeature?->final_attack_score ?? '—' }}<p class="text-[11px] text-slate-400">{{ $experiment->extractedFeature ? $scoreLabel($experiment->extractedFeature->attack_category) : 'Belum dianalisis' }}</p></td>
                        <td>{{ $experiment->aiResults->count() }} model</td>
                        <td class="text-right"><a href="{{ route('comparison.show', $experiment) }}" class="btn-primary text-xs">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6 text-slate-500">Belum ada eksperimen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $experiments->links() }}</div>
</div>
@endsection
