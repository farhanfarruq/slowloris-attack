<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $report->title }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.55; }
    h1 { font-size: 18px; color: #0e7490; margin-bottom: 0; }
    h2 { font-size: 13px; color: #0e7490; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-top: 20px; }
    .meta { font-size: 10px; color: #64748b; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    table.kv td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    table.kv td:first-child { width: 35%; color: #64748b; }
    table.scores td { padding: 4px 6px; border: 1px solid #e2e8f0; }
    .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; background: #ecfeff; color: #0e7490; }
    .footer { margin-top: 24px; font-size: 10px; color: #94a3b8; }
</style>
</head>
<body>

@php
    $exp = $report->experiment;
    $f = $exp?->extractedFeature;
    $aiResults = $exp?->aiResults ?? collect();
@endphp

<h1>{{ $report->title }}</h1>
<p class="meta">{{ $exp?->experiment_code }} · {{ $exp?->experiment_date?->format('d M Y') }} · Disusun oleh {{ $report->user->name ?? '-' }}</p>

<h2>1. Identitas Eksperimen</h2>
<table class="kv">
    <tr><td>Kode</td><td>{{ $exp?->experiment_code }}</td></tr>
    <tr><td>Nama</td><td>{{ $exp?->name }}</td></tr>
    <tr><td>Tipe Traffic</td><td>{{ str_replace('_', ' ', $exp?->traffic_type ?? '-') }}</td></tr>
    <tr><td>Ground Truth</td><td>{{ $exp?->ground_truth_label ?? '-' }}</td></tr>
    <tr><td>IP Target / Sumber</td><td>{{ $exp?->target_ip ?? '-' }} / {{ $exp?->source_ip ?? '-' }}</td></tr>
    <tr><td>Durasi Capture</td><td>{{ $exp?->capture_duration ?? 0 }} detik</td></tr>
</table>

<h2>2. Tujuan Eksperimen</h2>
<p>{{ $report->purpose }}</p>

<h2>3. Topologi Pengujian</h2>
<p>{{ $report->topology }}</p>

<h2>4. Tools yang Digunakan</h2>
<p>{{ $report->tools_used }}</p>

<h2>5. Hasil Wireshark/dumpcap & Snort</h2>
@if ($f)
<table class="kv">
    <tr><td>Total Packet</td><td>{{ number_format($f->total_packets) }}</td></tr>
    <tr><td>TCP / HTTP Packet</td><td>{{ number_format($f->tcp_packets) }} / {{ number_format($f->http_packets) }}</td></tr>
    <tr><td>Total Connections</td><td>{{ number_format($f->total_connections) }}</td></tr>
    <tr><td>Avg Connection Duration</td><td>{{ $f->avg_connection_duration }} dtk</td></tr>
    <tr><td>Throughput</td><td>{{ $f->throughput_kbps }} kbps</td></tr>
    <tr><td>Total Snort Alert</td><td>{{ $f->total_alerts }} (high {{ $f->high_severity_alerts }}, medium {{ $f->medium_severity_alerts }})</td></tr>
</table>
@else
<p>Fitur belum diekstrak.</p>
@endif

<h2>6. Skor Radar Indikator</h2>
@if ($f)
<table class="scores">
    <tr>
        <td>Connection Duration</td><td>{{ $f->connection_duration_score }}</td>
        <td>Header Anomaly</td><td>{{ $f->header_anomaly_score }}</td>
    </tr>
    <tr>
        <td>Low Bandwidth / High Conn</td><td>{{ $f->low_bandwidth_high_connection_score }}</td>
        <td>Snort Alert</td><td>{{ $f->snort_alert_score }}</td>
    </tr>
    <tr>
        <td>TCP Connection</td><td>{{ $f->tcp_connection_score }}</td>
        <td>Baseline Deviation</td><td>{{ $f->baseline_deviation_score }}</td>
    </tr>
    <tr>
        <td>AI Confidence</td><td>{{ $f->ai_confidence_score }}</td>
        <td><strong>Final Attack Score</strong></td><td><strong>{{ $f->final_attack_score }} ({{ $f->attack_category }})</strong></td>
    </tr>
</table>
@endif

<h2>7. Hasil Validasi AI</h2>
@if ($aiResults->count())
<table class="scores">
    <tr><td><strong>Model</strong></td><td><strong>Klasifikasi</strong></td><td><strong>Confidence</strong></td><td><strong>Reason</strong></td></tr>
    @foreach ($aiResults as $r)
        <tr>
            <td>{{ $r->model_name }} {{ $r->is_simulated ? '(sim)' : '' }}</td>
            <td>{{ $r->classification }}</td>
            <td>{{ $r->confidence_score }}%</td>
            <td>{{ \Illuminate\Support\Str::limit($r->reason, 180) }}</td>
        </tr>
    @endforeach
</table>
@else
<p>Belum ada hasil AI.</p>
@endif

<h2>8. Voting & Keputusan Akhir</h2>
<table class="kv">
    <tr><td>Final Decision</td><td>{{ $report->final_decision }}</td></tr>
    <tr><td>Avg Confidence</td><td>{{ $report->voting_average_confidence }}%</td></tr>
</table>

<h2>9. Kesimpulan</h2>
<p>{{ $report->conclusion }}</p>

<h2>10. Catatan Batasan</h2>
<p>{{ $report->limitations }}</p>

<div style="margin-top: 20px; padding: 10px; border: 1px solid #f59e0b; background: #fffbeb; color: #92400e; font-size: 11px; border-radius: 4px;">
    <strong>Disclaimer wajib:</strong>
    Hasil ini hanya berlaku untuk eksperimen pada VM lab lokal terisolasi (subnet 192.168.56.0/24).
    Klasifikasi seperti "Serangan asli" hanya muncul ketika bukti gabungan (Wireshark + Snort + pola koneksi)
    memenuhi gate evidence sistem; klasifikasi "Indikasi Slowloris" / "Suspicious" berarti diperlukan validasi lanjutan.
    Hasil ini tidak boleh digeneralisasi ke lingkungan produksi atau dipakai sebagai bukti tunggal serangan ke target publik.
</div>

<h2>11. Rekomendasi Pengembangan ke ESP32</h2>
<p>{{ $report->recommendations }}</p>

<div class="footer">
    Slowloris Lab - {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
