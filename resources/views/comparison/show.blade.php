@extends('layouts.app')

@section('title', 'Comparison · ' . $experiment->experiment_code)
@section('subtitle', $experiment->name)

@section('content')
@php
    $scoreLabel = fn (?string $category) => \App\Support\AttackPresentation::scoreLabel($category);
    $classificationLabel = fn (?string $classification) => \App\Support\AttackPresentation::classificationLabel($classification);
@endphp
<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">
    <div class="card p-4">
        <p class="text-xs uppercase tracking-wider text-slate-500">Tool Profile</p>
        <p class="text-xl font-semibold text-cyan-300 mt-1">{{ strtoupper($experiment->tool_profile ?? 'slowloris') }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs uppercase tracking-wider text-slate-500">Attack Pattern</p>
        <p class="text-xl font-semibold text-slate-100 mt-1">{{ $experiment->attack_pattern ?? $experiment->scenario_key ?? '—' }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs uppercase tracking-wider text-slate-500">Logic Score</p>
        <p class="text-xl font-semibold text-amber-300 mt-1">{{ $experiment->extractedFeature?->final_attack_score ?? '—' }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs uppercase tracking-wider text-slate-500">AI Results</p>
        <p class="text-xl font-semibold text-emerald-300 mt-1">{{ $experiment->aiResults->count() }}</p>
    </div>
</div>

<div class="card">
    <div class="card-header"><p class="card-title">Logic Program vs AI Analysis</p></div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead><tr><th>Logic Classification</th><th>Logic Score</th><th>AI Classification</th><th>AI Confidence</th><th>Agreement</th><th>Gate Reasons</th><th>Missing Evidence</th><th>Recommendation</th></tr></thead>
            <tbody>
                @foreach ($comparisons as $cmp)
                    <tr>
                        <td>{{ $scoreLabel($cmp['logic_classification'] ?? null) }}</td>
                        <td class="font-mono text-cyan-300">{{ $cmp['logic_score'] }}</td>
                        <td>{{ $classificationLabel($cmp['ai_classification'] ?? null) }}</td>
                        <td class="font-mono text-amber-300">{{ $cmp['ai_confidence'] }}%</td>
                        <td><span class="badge-cyan">{{ $cmp['agreement'] }}</span></td>
                        <td class="text-xs text-slate-400">@foreach ($cmp['gate_reasons'] as $reason)<div>• {{ $reason }}</div>@endforeach</td>
                        <td class="text-xs text-slate-400">@foreach ($cmp['missing_evidence'] as $missing)<div>• {{ $missing }}</div>@endforeach</td>
                        <td class="text-xs text-slate-300">{{ $cmp['recommendation'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
