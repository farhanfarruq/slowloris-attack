@extends('layouts.app')

@section('title', 'Upload Data Akuisisi')
@section('subtitle', 'Unggah file hasil capture Wireshark/dumpcap (.pcap, .pcapng, .csv, .json).')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card lg:col-span-1">
        <div class="card-header"><p class="card-title">Form Upload Akuisisi</p></div>
        @auth
            @if (auth()->user()->isAdmin())
                <form action="{{ route('acquisition.store') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="label-field">Pilih Eksperimen *</label>
                        <select name="experiment_id" class="input-field" required>
                            @foreach ($experiments as $e)
                                <option value="{{ $e->id }}" @selected(request('exp')==$e->id)>
                                    {{ $e->experiment_code }} — {{ $e->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-field">Label Capture *</label>
                        <input type="text" name="capture_label" required class="input-field"
                               value="{{ old('capture_label') }}"
                               placeholder="slow-http-20260529-01">
                        <p class="text-[11px] text-slate-500 mt-1">Label ini dipakai untuk memasangkan PCAP dengan log Snort.</p>
                    </div>
                    <div>
                        <label class="label-field">Kode Skenario</label>
                        <input type="text" name="scenario_key" class="input-field" list="scenario-options"
                               value="{{ old('scenario_key') }}"
                               placeholder="kosongkan untuk mengikuti eksperimen">
                        <datalist id="scenario-options">
                            <option value="normal-baseline">
                            <option value="http-burst">
                            <option value="slow-http">
                            <option value="portscan">
                            <option value="iperf-bandwidth">
                            <option value="loic">
                            <option value="hoic">
                            <option value="hping3">
                            <option value="torshammer">
                            <option value="xerxes">
                            <option value="manual">
                        </datalist>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label-field">IP Sumber</label>
                            <input type="text" name="source_ip" class="input-field" value="{{ old('source_ip') }}" placeholder="192.168.56.102">
                        </div>
                        <div>
                            <label class="label-field">IP Target</label>
                            <input type="text" name="target_ip" class="input-field" value="{{ old('target_ip') }}" placeholder="192.168.56.103">
                        </div>
                    </div>
                    <div>
                        <label class="label-field">File (.pcap / .pcapng / .csv / .json) *</label>
                        <input type="file" name="file" required class="input-field" accept=".pcap,.pcapng,.csv,.json">
                        <p class="text-[11px] text-slate-500 mt-1">Maks {{ config('upload.max_size_mb') }} MB. Nama file akan disanitasi otomatis.</p>
                    </div>
                    <div class="text-[11px] text-slate-500 space-y-1">
                        <p>File akuisisi adalah bukti packet capture dari Wireshark/dumpcap untuk satu sesi eksperimen.</p>
                        <p>Jangan campur PCAP dari skenario berbeda pada label capture yang sama.</p>
                    </div>
                    <button class="btn-primary w-full justify-center"><x-icon name="upload" class="w-4 h-4"/> Upload Data Akuisisi</button>
                </form>
            @else
                <div class="p-5 text-sm text-slate-500">Anda login sebagai Viewer. Hanya admin yang dapat mengunggah file.</div>
            @endif
        @endauth
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Riwayat & Preview Data Akuisisi</p></div>
        <div class="overflow-x-auto">
            <table class="table-stripe">
                <thead>
                    <tr>
                        <th>File</th><th>Label</th><th>Eksperimen</th><th>Pkt</th><th>TCP</th><th>HTTP</th>
                        <th>Conn</th><th>Avg Pkt Size</th><th>Top Src</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($files as $f)
                        @php $topSrc = collect($f->top_source_ips ?? [])->keys()->first(); @endphp
                        <tr>
                            <td class="text-slate-200">{{ $f->original_name }}<p class="text-[11px] text-slate-500">{{ $f->extension }} · {{ round($f->size_bytes/1024,1) }} KB · {{ $f->created_at->format('d M H:i') }}</p></td>
                            <td class="text-xs text-slate-300">
                                <span class="font-mono">{{ $f->capture_label ?? '—' }}</span>
                                <p class="text-[11px] text-slate-500">{{ $f->scenario_key ?? 'no-scenario' }}</p>
                            </td>
                            <td><a class="text-cyan-300" href="{{ route('experiments.show', $f->experiment) }}">{{ $f->experiment->experiment_code }}</a></td>
                            <td class="font-mono">{{ number_format($f->total_packets ?? 0) }}</td>
                            <td class="font-mono">{{ number_format($f->tcp_packets ?? 0) }}</td>
                            <td class="font-mono">{{ number_format($f->http_packets ?? 0) }}</td>
                            <td class="font-mono">{{ number_format($f->total_connections ?? 0) }}</td>
                            <td class="font-mono">{{ round($f->avg_packet_size ?? 0, 1) }}</td>
                            <td class="font-mono text-xs">{{ $topSrc ?? '—' }}</td>
                            <td class="text-right">
                                @auth @if (auth()->user()->isAdmin())
                                    <form action="{{ route('acquisition.destroy', $f) }}" method="POST" onsubmit="return confirm('Hapus?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-300 text-xs">Hapus</button>
                                    </form>
                                @endif @endauth
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-6 text-slate-500">Belum ada file akuisisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $files->links() }}</div>
    </div>
</div>

@endsection
