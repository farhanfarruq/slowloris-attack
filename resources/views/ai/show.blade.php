@extends('layouts.app')

@section('title', 'Validasi AI · ' . $experiment->experiment_code)
@section('subtitle', $experiment->name)

@section('content')

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="card xl:col-span-1">
        <div class="card-header"><p class="card-title">Jalankan Validasi AI</p></div>
        @auth @if (auth()->user()->isAdmin())
            <form action="{{ route('ai.run', $experiment) }}" method="POST" class="p-5 space-y-3">
                @csrf
                <p class="text-xs text-slate-500">Pilih provider live. Sistem mengirim ringkasan fitur, bukan file mentah.</p>
                @error('providers')
                    <div class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-950">{{ $message }}</div>
                @enderror
                @foreach ($providers as $p)
                    <label class="flex items-start gap-3 p-3 rounded-lg bg-white border border-gray-200 hover:border-blue-500 cursor-pointer">
                        <input type="checkbox" name="providers[]" value="{{ $p['key'] }}"
                               class="mt-1 h-5 w-5 rounded border-gray-400 text-blue-600 focus:ring-blue-500" {{ $p['can_run'] ? 'checked' : '' }}>
                        <div class="text-sm">
                            <p class="text-gray-950 font-semibold">{{ $p['label'] }}</p>
                            <p class="text-[11px] text-gray-600">{{ $p['driver'] }} · {{ $p['model'] ?? '—' }}</p>
                            @if ($p['can_run'])
                                <p class="text-[11px] text-emerald-700 font-medium mt-1">Siap dijalankan.</p>
                            @else
                                <p class="text-[11px] text-amber-700 font-medium mt-1">Belum siap: aktifkan live API dan isi kredensial bila diperlukan.</p>
                            @endif
                        </div>
                    </label>
                @endforeach
                <button class="btn-primary w-full justify-center">
                    <x-icon name="cpu" class="w-4 h-4"/> Jalankan Validasi
                </button>
                <p class="text-[11px] text-slate-500">Provider boleh dipilih, tetapi backend hanya menjalankan provider yang sudah live dan punya kredensial valid.</p>
            </form>
        @else
            <p class="p-5 text-sm text-slate-500">Hanya peneliti yang dapat menjalankan validasi AI.</p>
        @endif @endauth
    </div>

    <div class="card xl:col-span-2">
        <div class="card-header">
            <p class="card-title">Voting Final</p>
            <a href="{{ route('ai.export', $experiment) }}" class="text-xs text-emerald-300">Export JSON</a>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg p-4 bg-slate-950/60 border border-slate-800">
                <p class="text-xs uppercase tracking-wider text-slate-500">Final Decision</p>
                <p class="text-xl font-semibold text-cyan-300 mt-1">{{ $vote['final_decision'] }}</p>
            </div>
            <div class="rounded-lg p-4 bg-slate-950/60 border border-slate-800">
                <p class="text-xs uppercase tracking-wider text-slate-500">Avg Confidence</p>
                <p class="text-xl font-semibold text-amber-300 mt-1">{{ $vote['voting_average_confidence'] }}%</p>
            </div>
            <div class="rounded-lg p-4 bg-slate-950/60 border border-slate-800">
                <p class="text-xs uppercase tracking-wider text-slate-500">Top Klasifikasi</p>
                <p class="text-xl font-semibold text-emerald-300 mt-1">{{ $vote['voting_summary']['top_classification'] ?? '—' }}</p>
            </div>
        </div>

        @if (!empty($vote['voting_summary']['tally']))
            <div class="px-5 pb-5">
                <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Tally</p>
                <div class="flex gap-2 flex-wrap">
                    @foreach ($vote['voting_summary']['tally'] as $cls => $cnt)
                        <span class="badge-cyan">{{ $cls }} ({{ $cnt }})</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><p class="card-title">Hasil Per Model</p></div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead><tr><th>Model</th><th>Klasifikasi</th><th>Confidence</th><th>Reason</th><th>Indikator</th><th>Missing Evidence</th><th>Rekomendasi</th></tr></thead>
            <tbody>
                @forelse ($results as $r)
                    @php $color = ['Slowloris Detected'=>'rose','Suspicious'=>'amber','Normal'=>'emerald','Inconclusive'=>'slate'][$r->classification] ?? 'slate'; @endphp
                    <tr>
                        <td class="text-slate-100 font-medium">{{ $r->model_name }}<p class="text-[11px] text-slate-500">{{ $r->model_version }} {{ $r->is_simulated ? '(simulasi)':'' }}</p></td>
                        <td><span class="badge bg-{{ $color }}-500/15 text-{{ $color }}-300 border-{{ $color }}-500/30">{{ $r->classification }}</span></td>
                        <td class="font-mono text-cyan-300">{{ $r->confidence_score }}%</td>
                        <td class="text-slate-300 max-w-md">{{ $r->reason }}</td>
                        <td class="text-xs text-slate-400 max-w-xs">
                            @foreach ((array)$r->supporting_indicators as $ind)<div>• {{ $ind }}</div>@endforeach
                        </td>
                        <td class="text-xs text-slate-400 max-w-xs">
                            @foreach ((array)$r->missing_evidence as $m)<div>• {{ $m }}</div>@endforeach
                        </td>
                        <td class="text-xs text-slate-300 max-w-xs">{{ $r->recommendation }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">Belum ada hasil AI. Jalankan validasi terlebih dahulu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
