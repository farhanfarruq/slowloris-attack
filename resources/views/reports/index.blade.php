@extends('layouts.app')

@section('title', 'Daftar Laporan')

@section('content')

<div class="card">
    <div class="card-header"><p class="card-title">Laporan</p></div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead><tr><th>Eksperimen</th><th>Judul</th><th>Keputusan</th><th>Confidence</th><th>Dibuat</th><th></th></tr></thead>
            <tbody>
                @forelse ($reports as $r)
                    <tr>
                        <td class="font-mono text-cyan-300">{{ $r->experiment->experiment_code ?? '—' }}</td>
                        <td>{{ $r->title }}</td>
                        <td>
                            @php
                                $color = match (true) {
                                    $r->final_decision === 'Serangan asli' => 'rose',
                                    $r->final_decision === 'Traffic normal' => 'emerald',
                                    str_starts_with((string) $r->final_decision, 'Indikasi Slowloris') => 'amber',
                                    default => 'amber',
                                };
                            @endphp
                            <span class="badge bg-{{ $color }}-500/15 text-{{ $color }}-300 border-{{ $color }}-500/30">{{ $r->final_decision }}</span>
                        </td>
                        <td class="font-mono">{{ $r->voting_average_confidence }}%</td>
                        <td class="text-slate-400">{{ $r->created_at->format('d M Y') }}</td>
                        <td class="text-right space-x-2">
                            <a href="{{ route('reports.show', $r) }}" class="text-cyan-300 text-xs">Detail</a>
                            <a href="{{ route('reports.pdf', $r) }}" class="text-emerald-300 text-xs">PDF</a>
                            @auth @if (auth()->user()->isAdmin())
                                <form action="{{ route('reports.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirm('Hapus laporan?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-300 text-xs">Hapus</button>
                                </form>
                            @endif @endauth
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6 text-slate-500">Belum ada laporan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $reports->links() }}</div>
</div>

@endsection
