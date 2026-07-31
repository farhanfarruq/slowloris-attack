@extends('layouts.app')

@section('title', 'Evaluasi Per Profile')
@section('subtitle', 'Confusion matrix, accuracy, precision, recall, dan F1-score berdasarkan ground truth lab per tool profile.')

@section('content')

<div class="flex flex-wrap justify-end gap-2 mb-4">
    <a class="btn-ghost text-xs" href="{{ route('evaluation.export', 'json') }}">Export JSON</a>
    <a class="btn-ghost text-xs" href="{{ route('evaluation.export', 'csv') }}">Export CSV</a>
    <a class="btn-ghost text-xs" href="{{ route('evaluation.export', 'md') }}">Export Markdown</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Profile Accuracy</p>
        <p class="mt-2 text-3xl font-semibold text-cyan-300">{{ $metrics['accuracy'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">Tool profile benar / total sampel</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Precision</p>
        <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ $metrics['precision'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">Attack benar dengan profile tepat</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Recall</p>
        <p class="mt-2 text-3xl font-semibold text-amber-300">{{ $metrics['recall'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">Attack tertangkap pada profile tepat</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">F1 Score</p>
        <p class="mt-2 text-3xl font-semibold text-fuchsia-300">{{ $metrics['f1'] }}<span class="text-base">%</span></p>
        <p class="text-[11px] text-slate-500 mt-1">Rata-rata harmonic precision & recall</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Sample</p>
        <p class="mt-2 text-3xl font-semibold text-white">{{ $metrics['total'] }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Eksperimen siap evaluasi</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Profile Mismatch</p>
        <p class="mt-2 text-3xl font-semibold text-rose-300">{{ $metrics['pm'] }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Attack terdeteksi pada tool profile salah</p>
    </div>
</div>

	<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Total Eksperimen</p>
        <p class="mt-2 text-2xl font-semibold">{{ $coverage['total_experiments'] }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Semua data di database</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Siap Evaluasi</p>
        <p class="mt-2 text-2xl font-semibold">{{ $coverage['ready'] }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Punya ground truth dan status final</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Pending/Inconclusive</p>
        <p class="mt-2 text-2xl font-semibold">{{ $coverage['pending_or_inconclusive'] }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Belum dihitung evaluasi</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-slate-400 uppercase tracking-wider">Ground Truth Belum Siap</p>
        <p class="mt-2 text-2xl font-semibold">{{ $coverage['missing_ground_truth'] }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Kosong atau unknown</p>
	    </div>
	</div>

	<div class="card mb-4">
	    <div class="card-header"><p class="card-title">Dataset Coverage Per Profile</p></div>
	    <div class="overflow-x-auto">
	        <table class="table-stripe">
	            <thead>
	                <tr>
	                    <th>Profile</th>
	                    <th>Status</th>
	                    <th>Attack</th>
	                    <th>Normal</th>
	                    <th>Guard</th>
	                    <th>Ready</th>
	                    <th>Review</th>
	                    <th>Incomplete</th>
	                    <th>Gap</th>
	                </tr>
	            </thead>
	            <tbody>
	                @foreach ($datasetCoverage as $profile)
	                    @php
	                        $coverageColor = match ($profile['status']) {
	                            'Ready' => 'emerald',
	                            'Needs Review' => 'amber',
	                            default => 'rose',
	                        };
	                    @endphp
	                    <tr>
	                        <td class="font-semibold">{{ $profile['label'] }}</td>
	                        <td><span class="badge bg-{{ $coverageColor }}-500/15 text-{{ $coverageColor }}-300 border-{{ $coverageColor }}-500/30">{{ $profile['status'] }}</span></td>
	                        <td class="font-mono">{{ $profile['attack_samples'] }}</td>
	                        <td class="font-mono">{{ $profile['normal_samples'] }}</td>
	                        <td class="font-mono">{{ $profile['false_positive_guard_samples'] }}</td>
	                        <td class="font-mono">{{ $profile['ready_for_evaluation'] }}</td>
	                        <td class="font-mono">{{ $profile['needs_review'] }}</td>
	                        <td class="font-mono">{{ $profile['incomplete'] }}</td>
	                        <td class="text-xs text-slate-500">{{ $profile['missing_evidence'] ? implode(', ', array_slice($profile['missing_evidence'], 0, 3)) : '-' }}</td>
	                    </tr>
	                @endforeach
	            </tbody>
	        </table>
	    </div>
	</div>

		<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
	    <div class="card">
	        <div class="card-header"><p class="card-title">False Positive Guard</p></div>
	        <div class="overflow-x-auto">
	            <table class="table-stripe">
	                <thead>
	                    <tr>
	                        <th>Kategori</th>
	                        <th>Total</th>
	                        <th>TN</th>
	                        <th>Review</th>
	                        <th>FP</th>
	                        <th>FPR</th>
	                    </tr>
	                </thead>
	                <tbody>
	                    @foreach ($falsePositiveGuards as $guard)
	                        <tr>
	                            <td class="font-semibold">{{ $guard['label'] }}</td>
	                            <td class="font-mono">{{ $guard['total'] }}</td>
	                            <td class="font-mono">{{ $guard['tn'] }}</td>
	                            <td class="font-mono">{{ $guard['review'] }}</td>
	                            <td class="font-mono">{{ $guard['fp'] }}</td>
	                            <td class="font-mono">{{ $guard['false_positive_rate'] }}%</td>
	                        </tr>
	                    @endforeach
	                </tbody>
	            </table>
	        </div>
	    </div>

	    <div class="card">
	        <div class="card-header"><p class="card-title">AI Disagreement Queue</p></div>
	        <div class="overflow-x-auto">
	            <table class="table-stripe">
	                <thead>
	                    <tr>
	                        <th>Eksperimen</th>
	                        <th>Kategori</th>
	                        <th>Logic</th>
	                        <th>AI</th>
	                        <th>Confidence</th>
	                    </tr>
	                </thead>
	                <tbody>
	                    @forelse ($aiDisagreements as $row)
	                        <tr>
	                            <td>
	                                <a class="text-cyan-300" href="{{ route('experiments.show', $row['experiment']) }}">{{ $row['experiment']->experiment_code }}</a>
	                                <p class="text-[11px] text-slate-500">{{ $row['model'] }}</p>
	                            </td>
	                            <td class="text-slate-300">{{ $row['category'] }}</td>
	                            <td class="font-mono">{{ $row['logic'] }}</td>
	                            <td class="font-mono">{{ $row['classification'] }}</td>
	                            <td class="font-mono">{{ $row['confidence'] }}%</td>
	                        </tr>
	                    @empty
	                        <tr><td colspan="5" class="text-center py-6 text-slate-500">Belum ada perbedaan logic dan AI.</td></tr>
	                    @endforelse
	                </tbody>
	            </table>
	        </div>
		    </div>
		</div>

		<div class="card mb-4">
		    <div class="card-header">
		        <p class="card-title">Profile Calibration Review</p>
		        <div class="flex gap-2">
		            <a class="text-xs text-cyan-300" href="{{ route('evaluation.calibration.export', array_merge(request()->query(), ['format' => 'md'])) }}">Export MD</a>
		            <a class="text-xs text-emerald-300" href="{{ route('evaluation.calibration.export', array_merge(request()->query(), ['format' => 'json'])) }}">Export JSON</a>
		        </div>
		    </div>
		    <div class="p-5">
		        <form method="GET" action="{{ route('evaluation.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
		            <div>
		                <label class="block text-xs text-slate-500 mb-1">Profile</label>
		                <select name="calibration_profile" class="input-field">
		                    @foreach ($calibrationProfiles as $profile)
		                        <option value="{{ $profile['key'] }}" @selected($calibrationResult['profile'] === $profile['key'])>{{ $profile['label'] }}</option>
		                    @endforeach
		                </select>
		            </div>
		            <div>
		                <label class="block text-xs text-slate-500 mb-1">Detected Threshold</label>
		                <input name="detected" type="number" min="0" max="100" step="1" class="input-field" value="{{ $calibrationResult['detected_threshold'] }}">
		            </div>
		            <div>
		                <label class="block text-xs text-slate-500 mb-1">Suspicious Threshold</label>
		                <input name="suspicious" type="number" min="0" max="100" step="1" class="input-field" value="{{ $calibrationResult['suspicious_threshold'] }}">
		            </div>
		            <button class="btn-primary text-xs">Simulasikan</button>
		        </form>

		        @auth
		            @if(auth()->user()->isAdmin())
		                <form method="POST" action="{{ route('evaluation.calibration.snapshots.store') }}" class="mt-3">
		                    @csrf
		                    <input type="hidden" name="calibration_profile" value="{{ $calibrationResult['profile'] }}">
		                    <input type="hidden" name="detected" value="{{ $calibrationResult['detected_threshold'] }}">
		                    <input type="hidden" name="suspicious" value="{{ $calibrationResult['suspicious_threshold'] }}">
		                    <button class="btn-success text-xs">Simpan Snapshot</button>
		                </form>
		            @endif
		        @endauth

		        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mt-5 text-sm">
		            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
		                <p class="text-xs text-slate-500">Sample</p>
		                <p class="text-xl font-semibold">{{ $calibrationResult['metrics']['total'] }}</p>
		            </div>
		            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
		                <p class="text-xs text-slate-500">Accuracy</p>
		                <p class="text-xl font-semibold">{{ $calibrationResult['metrics']['accuracy'] }}%</p>
		            </div>
		            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
		                <p class="text-xs text-slate-500">Precision</p>
		                <p class="text-xl font-semibold">{{ $calibrationResult['metrics']['precision'] }}%</p>
		            </div>
		            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
		                <p class="text-xs text-slate-500">Recall</p>
		                <p class="text-xl font-semibold">{{ $calibrationResult['metrics']['recall'] }}%</p>
		            </div>
		            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
		                <p class="text-xs text-slate-500">F1</p>
		                <p class="text-xl font-semibold">{{ $calibrationResult['metrics']['f1'] }}%</p>
		            </div>
		            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
		                <p class="text-xs text-slate-500">Changed</p>
		                <p class="text-xl font-semibold">{{ count($calibrationResult['changed']) }}</p>
		            </div>
		        </div>

		        <p class="text-[11px] text-slate-500 mt-3">Simulasi ini tidak mengubah status eksperimen asli.</p>

		        @if($calibrationResult['changed'])
		            <div class="overflow-x-auto mt-4">
		                <table class="table-stripe">
		                    <thead>
		                        <tr>
		                            <th>Kode</th>
		                            <th>Score</th>
		                            <th>Status Sekarang</th>
		                            <th>Simulasi</th>
		                            <th>Type</th>
		                        </tr>
		                    </thead>
		                    <tbody>
		                        @foreach (array_slice($calibrationResult['changed'], 0, 10) as $row)
		                            <tr>
		                                <td class="font-mono text-cyan-300">{{ $row['code'] }}</td>
		                                <td class="font-mono">{{ $row['score'] }}</td>
		                                <td class="font-mono">{{ $row['current'] }}</td>
		                                <td class="font-mono">{{ $row['simulated'] }}</td>
		                                <td class="font-mono">{{ $row['type'] }}</td>
		                            </tr>
		                        @endforeach
		                    </tbody>
		                </table>
		            </div>
		        @endif

		        @if($calibrationSnapshots->isNotEmpty())
		            <div class="mt-5">
		                <p class="text-sm font-semibold text-slate-200 mb-2">Snapshot Terakhir</p>
		                <div class="overflow-x-auto">
		                    <table class="table-stripe">
		                        <thead>
		                            <tr>
		                                <th>Waktu</th>
		                                <th>Profile</th>
		                                <th>Detected</th>
		                                <th>Suspicious</th>
		                                <th>F1</th>
		                                <th>Changed</th>
		                                <th>User</th>
		                            </tr>
		                        </thead>
		                        <tbody>
		                            @foreach($calibrationSnapshots as $snapshot)
		                                <tr>
		                                    <td class="text-xs text-slate-500">{{ $snapshot->created_at->format('d M Y H:i') }}</td>
		                                    <td class="font-mono">{{ $snapshot->tool_profile }}</td>
		                                    <td class="font-mono">{{ $snapshot->detected_threshold }}</td>
		                                    <td class="font-mono">{{ $snapshot->suspicious_threshold }}</td>
		                                    <td class="font-mono">{{ $snapshot->metrics['f1'] ?? 0 }}%</td>
		                                    <td class="font-mono">{{ count($snapshot->changed_rows ?? []) }}</td>
		                                    <td class="text-slate-300">{{ $snapshot->user?->name ?? '-' }}</td>
		                                </tr>
		                            @endforeach
		                        </tbody>
		                    </table>
		                </div>
		            </div>
		        @endif
		    </div>
		</div>
		
		<div class="card mb-4">
    <div class="card-header"><p class="card-title">Arti Kode Evaluasi</p></div>
    <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 text-sm">
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3">
            <p class="font-semibold">TP - True Positive</p>
            <p class="text-xs text-slate-500 mt-1">Ground truth attack, sistem juga mendeteksi attack. Ini hasil benar.</p>
        </div>
        <div class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 p-3">
            <p class="font-semibold">TN - True Negative</p>
            <p class="text-xs text-slate-500 mt-1">Ground truth normal, sistem juga menyatakan normal. Ini hasil benar.</p>
        </div>
        <div class="rounded-lg border border-rose-500/30 bg-rose-500/10 p-3">
            <p class="font-semibold">FP - False Positive</p>
            <p class="text-xs text-slate-500 mt-1">Ground truth normal, tetapi sistem curiga atau mendeteksi attack. Ini alarm palsu.</p>
        </div>
        <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3">
            <p class="font-semibold">FN - False Negative</p>
            <p class="text-xs text-slate-500 mt-1">Ground truth attack, tetapi sistem tidak mendeteksi attack. Ini miss detection.</p>
        </div>
        <div class="rounded-lg border border-rose-500/30 bg-rose-500/10 p-3">
            <p class="font-semibold">PM - Profile Mismatch</p>
            <p class="text-xs text-slate-500 mt-1">Sistem mendeteksi attack, tetapi tool profile tidak sama dengan ground truth.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card lg:col-span-1">
        <div class="card-header"><p class="card-title">Confusion Matrix Profile-Aware</p></div>
        <div class="p-5">
            <div class="grid grid-cols-3 gap-2 text-center text-sm">
                <div></div>
                <div class="p-2 text-[11px] text-slate-500">Prediksi: Normal</div>
                <div class="p-2 text-[11px] text-slate-500">Prediksi: Attack</div>

                <div class="p-2 text-[11px] text-slate-500 text-right">Aktual: Normal</div>
                <div class="p-3 rounded-lg border border-cyan-500/30 bg-cyan-500/10">
                    <p class="text-2xl font-semibold text-cyan-300">{{ $metrics['tn'] }}</p>
                    <p class="text-[10px] text-cyan-300/70">TN</p>
                </div>
                <div class="p-3 rounded-lg border border-rose-500/30 bg-rose-500/10">
                    <p class="text-2xl font-semibold text-rose-300">{{ $metrics['fp'] + $metrics['pm'] }}</p>
                    <p class="text-[10px] text-rose-300/70">FP + PM</p>
                </div>

                <div class="p-2 text-[11px] text-slate-500 text-right">Aktual: Attack</div>
                <div class="p-3 rounded-lg border border-amber-500/30 bg-amber-500/10">
                    <p class="text-2xl font-semibold text-amber-300">{{ $metrics['fn'] + $metrics['pm'] }}</p>
                    <p class="text-[10px] text-amber-300/70">FN + PM</p>
                </div>
                <div class="p-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10">
                    <p class="text-2xl font-semibold text-emerald-300">{{ $metrics['tp'] }}</p>
                    <p class="text-[10px] text-emerald-300/70">TP</p>
                </div>
            </div>

            <div class="mt-5 text-[11px] text-slate-500 space-y-1">
                <p>Ground truth <code>normal</code> dihitung sebagai Normal.</p>
                <p>Ground truth profile seperti <code>slowloris</code>, <code>loic</code>, <code>hping3</code>, dan sejenisnya dihitung sebagai Attack pada profile terkait.</p>
                <p>Jika sistem mendeteksi attack pada tool profile berbeda, hasil masuk <code>PM</code>, bukan <code>TP</code>.</p>
                <p>Status <code>suspicious</code> dihitung sebagai review. Untuk metrik ketat, review pada data attack masuk FN, review pada data normal masuk FP.</p>
                <p>Status <code>pending</code>, <code>inconclusive</code>, atau ground truth tidak jelas diabaikan dari evaluasi.</p>
                <p>Binary attack/normal: Accuracy {{ $binaryMetrics['accuracy'] }}%, Precision {{ $binaryMetrics['precision'] }}%, Recall {{ $binaryMetrics['recall'] }}%, F1 {{ $binaryMetrics['f1'] }}%.</p>
            </div>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Metrik Per Profile</p></div>
        <div class="overflow-x-auto">
            <table class="table-stripe">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Sample</th>
                        <th>TP</th>
                        <th>TN</th>
                        <th>FP</th>
                        <th>FN</th>
                        <th>PM</th>
                        <th>Accuracy</th>
                        <th>Precision</th>
                        <th>Recall</th>
                        <th>F1</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($profileMetrics as $profile)
                        <tr>
                            <td class="font-semibold">{{ $profile['label'] }}</td>
                            <td class="font-mono">{{ $profile['total'] }}</td>
                            <td class="font-mono">{{ $profile['tp'] }}</td>
                            <td class="font-mono">{{ $profile['tn'] }}</td>
                            <td class="font-mono">{{ $profile['fp'] }}</td>
                            <td class="font-mono">{{ $profile['fn'] }}</td>
                            <td class="font-mono">{{ $profile['pm'] }}</td>
                            <td class="font-mono">{{ $profile['accuracy'] }}%</td>
                            <td class="font-mono">{{ $profile['precision'] }}%</td>
                            <td class="font-mono">{{ $profile['recall'] }}%</td>
                            <td class="font-mono">{{ $profile['f1'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><p class="card-title">Detail per Eksperimen</p></div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Profile</th>
                    <th>Ground Truth</th>
                    <th>Prediksi Sistem</th>
	                    <th>Skor</th>
	                    <th>Quality</th>
	                    <th>Binary</th>
	                    <th>Profile</th>
	                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $color = match ($row['type']) {
                            'TP' => 'emerald',
                            'TN' => 'cyan',
                            'FP' => 'rose',
                            'FN' => 'amber',
                            'PM' => 'rose',
                        };
                        $binaryColor = match ($row['binary_type']) {
                            'TP' => 'emerald',
                            'TN' => 'cyan',
                            'FP' => 'rose',
                            'FN' => 'amber',
                            default => 'slate',
                        };
                    @endphp
                    <tr>
                        <td class="font-mono text-cyan-300">{{ $row['experiment']->experiment_code }}</td>
                        <td class="text-slate-200">{{ $row['experiment']->name }}</td>
                        <td class="text-slate-300">{{ $row['profile_label'] }}</td>
                        <td class="text-slate-300">
                            {{ ucfirst($row['actual']) }}
                            @if($row['actual_profile_label'])
                                <p class="text-[11px] text-slate-500">{{ $row['actual_profile_label'] }}</p>
                            @endif
                        </td>
                        <td class="text-slate-300">
                            {{ ucfirst($row['predicted']) }}
                            @if($row['predicted_profile_label'])
                                <p class="text-[11px] text-slate-500">{{ $row['predicted_profile_label'] }}</p>
                            @endif
                        </td>
                        <td class="font-mono">
	                            {{ $row['final_score'] ?? '-' }}
	                            <p class="text-[11px] text-slate-500">{{ $row['category'] }}</p>
	                        </td>
	                        @php
	                            $qualityColor = match ($row['quality']['status']) {
	                                'Ready' => 'emerald',
	                                'Needs Review' => 'amber',
	                                default => 'rose',
	                            };
	                        @endphp
	                        <td>
	                            <span class="badge bg-{{ $qualityColor }}-500/15 text-{{ $qualityColor }}-300 border-{{ $qualityColor }}-500/30">{{ $row['quality']['status'] }}</span>
	                            @if($row['quality']['reasons'])
	                                <p class="text-[11px] text-slate-500 mt-1">{{ implode(', ', array_slice($row['quality']['reasons'], 0, 2)) }}</p>
	                            @endif
	                        </td>
	                        <td>
	                            <span class="badge bg-{{ $binaryColor }}-500/15 text-{{ $binaryColor }}-300 border-{{ $binaryColor }}-500/30">{{ $row['binary_type'] }}</span>
	                        </td>
                        <td>
                            <span class="badge bg-{{ $color }}-500/15 text-{{ $color }}-300 border-{{ $color }}-500/30">{{ $row['type'] }}</span>
                        </td>
                    </tr>
                @empty
	                    <tr><td colspan="9" class="text-center py-6 text-slate-500">Belum ada eksperimen dengan ground truth dan status sistem yang siap dievaluasi.</td></tr>
	                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
