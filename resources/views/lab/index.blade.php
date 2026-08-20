@extends('layouts.app')

@section('title', 'Eksperimen Lab ESP32')
@section('subtitle', 'ESP32 fisik sebagai target HTTP; Ubuntu laptop sebagai client dan sensor.')

@section('content')

<div class="card mb-4">
    <div class="card-header">
        <p class="card-title">Topologi Lab Terisolasi</p>
        <span class="badge-emerald">Lab Aman · Target Allowlist</span>
    </div>
    <div class="p-5 text-sm text-slate-300 space-y-3">
        <p><strong>Ubuntu laptop</strong> menjalankan workflow eksperimen, dumpcap, TShark, dan Snort 3. <strong>ESP32</strong> hanya menjadi target web server fisik.</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div><p class="text-xs text-slate-500">ESP32 Target</p><p class="font-mono">{{ $target['host'] }}:{{ $target['port'] }}</p></div>
            <div><p class="text-xs text-slate-500">Capture Interface</p><p class="font-mono">{{ $target['capture_interface'] }}</p></div>
            <div><p class="text-xs text-slate-500">Chip</p><p class="font-mono">{{ data_get($target, 'device.chip') }} {{ data_get($target, 'device.revision') }}</p></div>
            <div><p class="text-xs text-slate-500">SoftAP</p><p class="font-mono">{{ $target['ssid'] }}</p></div>
        </div>
        <p class="text-xs text-slate-500">Web container tidak diberi akses privileged ke interface Wi-Fi host. Status live harus diperiksa dengan <code>php artisan esp32:readiness</code>.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><p class="card-title">Alur Eksperimen Baru</p></div>
    <ol class="p-5 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-slate-300 list-decimal list-inside">
        <li>Validasi allowlist dan interface host.</li>
        <li>Validasi <code>/health</code> dan metrics before.</li>
        <li>Mulai dumpcap pada {{ $target['capture_interface'] }}.</li>
        <li>Jalankan workflow lab terotorisasi secara manual.</li>
        <li>Hentikan capture dan baca metrics after.</li>
        <li>Validasi PCAP dengan TShark.</li>
        <li>Analisis offline memakai Snort 3.</li>
        <li>Hash dan impor metadata ke Laravel.</li>
    </ol>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card">
        <div class="card-header"><p class="card-title">Perintah Host Aman</p></div>
        <div class="p-5 text-sm text-slate-300 space-y-3">
            <pre class="p-3 bg-slate-950 rounded-lg overflow-x-auto"><code>php artisan esp32:readiness
scripts/esp32-lab/run.sh EXP-001 60</code></pre>
            <p>Runner tidak menghasilkan traffic serangan. Gunakan terminal lain hanya untuk workflow lab lama yang memang sudah diotorisasi.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Hasil ESP32 Terakhir</p></div>
        <div class="p-5 text-sm text-slate-300">
            @if ($latestMetadata)
                <dl class="grid grid-cols-2 gap-2">
                    <dt class="text-slate-500">Eksperimen</dt><dd>{{ $latestMetadata['experiment_id'] ?? '—' }}</dd>
                    <dt class="text-slate-500">Packets</dt><dd>{{ $latestMetadata['packet_count'] ?? '—' }}</dd>
                    <dt class="text-slate-500">Alerts</dt><dd>{{ $latestMetadata['alert_count'] ?? '—' }}</dd>
                    <dt class="text-slate-500">Analysis</dt><dd>{{ ($latestMetadata['analysis_success'] ?? false) ? 'SUCCESS' : 'INCOMPLETE' }}</dd>
                    <dt class="text-slate-500">Reset reason</dt><dd>{{ data_get($latestMetadata, 'esp32_metrics_after.reset_reason', '—') }}</dd>
                    <dt class="text-slate-500">Free heap</dt><dd>{{ data_get($latestMetadata, 'esp32_metrics_after.free_heap_bytes', '—') }}</dd>
                </dl>
            @else
                <p class="text-slate-500">Belum ada metadata eksperimen ESP32 yang dihasilkan runner host.</p>
            @endif
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><p class="card-title">Batas Keamanan dan Provenance</p></div>
    <div class="p-5 text-sm text-slate-300 space-y-2">
        <p>Target otomatis hanya diizinkan untuk IP ESP32 lab yang ada dalam allowlist. Domain publik, pencarian target, dan network scanning ditolak.</p>
        <p>Password SoftAP tidak disimpan atau ditampilkan. Record dan artefak VM lama tetap historis dan tidak diubah menjadi bukti ESP32.</p>
        <p><code>0 alert</code> dengan exit code Snort sukses merupakan hasil valid, bukan kegagalan analisis.</p>
    </div>
</div>

@endsection
