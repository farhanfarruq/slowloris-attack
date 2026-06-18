@extends('layouts.app')

@section('title', 'Proses Analisis')
@section('subtitle', 'Korelasi data akuisisi & validasi, lalu jalankan ekstraksi fitur dan AI Analysis.')

@section('content')

@php
    $scoreLabel = fn (?string $category) => \App\Support\AttackPresentation::scoreLabel($category);
@endphp

<div class="card">
    <div class="card-header">
        <p class="card-title">Indikator Analisis</p>
        <span class="text-xs text-slate-500">Indikator yang diperhitungkan oleh sistem:</span>
    </div>
    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        @foreach ([
            'Banyak koneksi HTTP yang terbuka lama',
            'Header HTTP tidak selesai / koneksi dibuat sangat lambat',
            'Koneksi TCP meningkat tetapi throughput rendah',
            'Banyak koneksi menuju port HTTP/HTTPS target lab',
            'Alert Snort meningkat pada rentang waktu yang sama',
            'Traffic tidak seimbang antara koneksi dan payload',
            'Pola berbeda dari baseline iPerf3 / traffic normal',
        ] as $idx => $point)
            <div class="flex items-start gap-2 p-3 rounded-lg bg-slate-950/60 border border-slate-800">
                <span class="text-cyan-400 mt-0.5">{{ str_pad($idx+1, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="text-slate-300">{{ $point }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <p class="card-title">Eksperimen Tersedia</p>
    </div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead>
                <tr><th>Kode</th><th>Nama</th><th>Akuisisi</th><th>Validasi</th><th>Pair</th><th>Skor</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($experiments as $exp)
                    @php
                        $latestAcq = $exp->acquisitionFiles->sortByDesc('created_at')->first();
                        $pairedVal = $latestAcq
                            ? $exp->validationFiles->where('acquisition_file_id', $latestAcq->id)->sortByDesc('created_at')->first()
                            : null;
                    @endphp
                    <tr>
                        <td class="font-mono text-cyan-300">{{ $exp->experiment_code }}</td>
                        <td class="text-slate-200">{{ $exp->name }}</td>
                        <td class="font-mono">{{ $exp->acquisitionFiles->count() }}</td>
                        <td class="font-mono">{{ $exp->validationFiles->count() }}</td>
                        <td>
                            @if ($latestAcq && $pairedVal)
                                <span class="badge-cyan">Siap</span>
                                <p class="text-[11px] text-slate-500">{{ $latestAcq->capture_label ?? 'tanpa-label' }}</p>
                            @else
                                <span class="badge-slate">Belum lengkap</span>
                            @endif
                        </td>
                        <td>
                            @if ($exp->extractedFeature)
                                <span class="font-mono text-amber-300">{{ $exp->extractedFeature->final_attack_score }}</span>
                                <p class="text-[11px] text-slate-500">{{ $scoreLabel($exp->extractedFeature->attack_category) }}</p>
                            @else <span class="text-xs text-slate-500">—</span>
                            @endif
                        </td>
                        <td><span class="badge-slate">{{ $exp->status }}</span></td>
                        <td class="space-x-1 flex flex-wrap gap-1">
                            @auth @if (auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('analysis.process', $exp) }}" class="inline">
                                    @csrf
                                    <button class="btn-primary text-xs" type="submit">Proses Analisis</button>
                                </form>
                                <form method="POST" action="{{ route('analysis.correlate', $exp) }}" class="inline">
                                    @csrf
                                    <button class="btn-ghost text-xs" type="submit">Korelasi</button>
                                </form>
                                <a href="{{ route('ai.show', $exp) }}" class="btn-ghost text-xs">AI Analysis</a>
                                <a href="{{ route('reports.create', $exp) }}" class="btn-success text-xs">Generate Laporan</a>
                            @else
                                <a href="{{ route('experiments.show', $exp) }}" class="btn-ghost text-xs">Detail</a>
                            @endif @endauth
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6 text-slate-500">Belum ada eksperimen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $experiments->links() }}</div>
</div>

@endsection
