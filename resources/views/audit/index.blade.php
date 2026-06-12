@extends('layouts.app')

@section('title', 'Audit Log')
@section('subtitle', 'Riwayat aktivitas seluruh user pada dashboard.')

@section('content')

<div class="card">
    <div class="card-header"><p class="card-title">Aktivitas Terbaru</p></div>
    <div class="overflow-x-auto">
        <table class="table-stripe">
            <thead>
                <tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Subject</th><th>IP</th><th>Detail</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $l)
                    <tr>
                        <td class="text-xs text-slate-400 font-mono">{{ $l->created_at->format('d M Y H:i:s') }}</td>
                        <td class="text-slate-200">{{ $l->user?->name ?? 'system' }}<p class="text-[11px] text-slate-500">{{ $l->user?->role }}</p></td>
                        <td><span class="badge-cyan">{{ $l->action }}</span></td>
                        <td class="text-xs text-slate-400 font-mono">
                            {{ class_basename($l->subject_type ?? '') }}#{{ $l->subject_id ?? '—' }}
                        </td>
                        <td class="text-xs font-mono text-slate-400">{{ $l->ip_address }}</td>
                        <td class="text-xs text-slate-400 max-w-md truncate">{{ json_encode($l->meta) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-8 text-slate-500">Belum ada aktivitas tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $logs->links() }}</div>
</div>

@endsection
