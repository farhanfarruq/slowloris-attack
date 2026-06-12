@extends('layouts.app')

@section('title', 'Evaluasi Akurasi Model')
@section('subtitle', 'Confusion matrix, accuracy, precision, recall, dan F1-score berdasarkan ground truth.')

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Accuracy</p>
        <p class="mt-2 text-3xl font-semibold text-cyan-300">{{ $metrics['accuracy'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">(TP + TN) / Total</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Precision</p>
        <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ $metrics['precision'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">TP / (TP + FP)</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Recall</p>
        <p class="mt-2 text-3xl font-semibold text-amber-300">{{ $metrics['recall'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">TP / (TP + FN)</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">F1 Score</p>
        <p class="mt-2 text-3xl font-semibold text-fuchsia-300">{{ $metrics['f1'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">2·P·R / (P+R)</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Sample</p>
        <p class="mt-2 text-3xl font-semibold text-white">{{ $metrics['total'] }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Eksperimen berlabel</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card lg:col-span-1">
        <div class="card-header"><p class="card-title">Confusion Matrix</p></div>
        <div class="p-5">
            <div class="grid grid-cols-3 gap-2 text-center text-sm">
                <div></div>
                <div class="p-2 text-[11px] text-slate-500">Pred: Normal</div>
                <div class="p-2 text-[11px] text-slate-500">Pred: Attack</div>

                <div class="p-2 text-[11px] text-slate-500 text-right">Actual: Normal</div>
                <div class="p-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10">
                    <p class="text-2xl font-semibold text-emerald-300">{{ $metrics['tn'] }}</p>
                    <p class="text-[10px] text-emerald-300/70">TN</p>
                </div>
                <div class="p-3 rounded-lg border border-rose-500/30 bg-rose-500/10">
                    <p class="text-2xl font-semibold text-rose-300">{{ $metrics['fp'] }}</p>
                    <p class="text-[10px] text-rose-300/70">FP</p>
                </div>

                <div class="p-2 text-[11px] text-slate-500 text-right">Actual: Attack</div>
                <div class="p-3 rounded-lg border border-rose-500/30 bg-rose-500/10">
                    <p class="text-2xl font-semibold text-rose-300">{{ $metrics['fn'] }}</p>
                    <p class="text-[10px] text-rose-300/70">FN</p>
                </div>
                <div class="p-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10">
                    <p class="text-2xl font-semibold text-emerald-300">{{ $metrics['tp'] }}</p>
                    <p class="text-[10px] text-emerald-300/70">TP</p>
                </div>
            </div>

            <div class="mt-5 text-[11px] text-slate-500 space-y-1">
                <p>• Ground truth <code>slowloris_lab</code> & <code>mixed</code> = Attack.</p>
                <p>• Status sistem <code>attack_detected</code> & <code>suspicious</code> = Attack.</p>
                <p>• Eksperimen tanpa ground truth atau status pending diabaikan.</p>
            </div>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Detail per Eksperimen</p></div>
        <div class="overflow-x-auto">
            <table class="table-stripe">
                <thead><tr><th>Kode</th><th>Nama</th><th>Ground Truth</th><th>Prediksi Sistem</th><th>Skor</th><th>Tipe</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $color = match ($row['type']) {
                                'TP' => 'emerald', 'TN' => 'cyan',
                                'FP' => 'rose',    'FN' => 'amber',
                            };
                        @endphp
                        <tr>
                            <td class="font-mono text-cyan-300">{{ $row['experiment']->experiment_code }}</td>
                            <td class="text-slate-200">{{ $row['experiment']->name }}</td>
                            <td class="text-slate-300">{{ $row['actual'] }}</td>
                            <td class="text-slate-300">{{ $row['predicted'] }}</td>
                            <td class="font-mono">{{ $row['final_score'] ?? '—' }}<p class="text-[11px] text-slate-500">{{ $row['category'] }}</p></td>
                            <td><span class="badge bg-{{ $color }}-500/15 text-{{ $color }}-300 border-{{ $color }}-500/30">{{ $row['type'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-6 text-slate-500">Belum ada eksperimen dengan ground truth label & status sistem yang siap dievaluasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
