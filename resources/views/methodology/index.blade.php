@extends('layouts.app')

@section('title', 'Alur Sistem')
@section('subtitle', 'Pendekatan, alat, scoring, dan evaluasi.')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card">
        <div class="card-header"><p class="card-title">Tahapan Proses</p></div>
        <ol class="p-5 space-y-3 list-decimal list-inside text-sm text-slate-300">
            <li>Akuisisi data menggunakan Wireshark/dumpcap (.pcap, .pcapng) di interface lab.</li>
            <li>Validasi deteksi menggunakan Snort 3 (mode IDS/IPS) untuk membandingkan rule signature.</li>
            <li>Baseline traffic normal menggunakan iPerf3 dan browsing lokal ke web server lab.</li>
            <li>Simulasi traffic Slow HTTP / Slowloris di dalam lab (SlowHTTPTest, slowloris.py).</li>
            <li>Ekstraksi fitur traffic & alert ke dalam ringkasan numerik.</li>
            <li>Validasi multi-model AI (Groq, OpenAI, Gemini, Ollama) untuk klasifikasi.</li>
            <li>Voting hasil multi-model + confidence rata-rata.</li>
            <li>Evaluasi akurasi: bandingkan klasifikasi sistem terhadap ground truth & alert IDS.</li>
        </ol>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Skala Skor Radar Chart (0–100)</p></div>
        <div class="p-5 text-sm text-slate-300 space-y-2">
            <p>Indikator yang dihitung pada saat ekstraksi fitur:</p>
            <ul class="list-disc list-inside text-slate-400 space-y-1">
                <li><span class="text-slate-200">Connection Duration Score</span> — durasi koneksi rata-rata vs ambang Slowloris (180s).</li>
                <li><span class="text-slate-200">Header Anomaly Score</span> — proporsi koneksi half-open / header tidak selesai.</li>
                <li><span class="text-slate-200">Low Bandwidth High Connection Score</span> — banyak koneksi tetapi throughput rendah.</li>
                <li><span class="text-slate-200">Snort Alert Score</span> — bobot alert berdasarkan severity (high×5, med×2, low×1).</li>
                <li><span class="text-slate-200">TCP Connection Score</span> — dominasi paket TCP & HTTP.</li>
                <li><span class="text-slate-200">Baseline Deviation Score</span> — deviasi terhadap baseline iPerf3/browsing.</li>
                <li><span class="text-slate-200">AI Confidence Score</span> — rata-rata confidence multi-model AI.</li>
            </ul>
            <p class="mt-2">Semua skor dinormalisasi pada skala <strong>0–100</strong>. Semakin tinggi skor, semakin kuat indikasi Slowloris.</p>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Final Attack Score</p></div>
        <div class="p-5 text-sm text-slate-300 space-y-3 font-mono">
            <pre class="p-4 rounded-lg bg-slate-950/80 border border-slate-800 text-xs overflow-x-auto">
Final Attack Score =
    0.20 × Connection Duration Score
  + 0.20 × Header Anomaly Score
  + 0.15 × Low Bandwidth High Connection Score
  + 0.20 × Snort Alert Score
  + 0.10 × TCP Connection Score
  + 0.10 × Baseline Deviation Score
  + 0.05 × AI Confidence Score</pre>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 font-sans">
                <div class="score-scale-card score-tone-normal">
                    <p class="score-scale-range">0–30</p>
                    <p class="score-scale-label">Normal</p>
                </div>
                <div class="score-scale-card score-tone-suspicious">
                    <p class="score-scale-range">31–55</p>
                    <p class="score-scale-label">Suspicious</p>
                </div>
                <div class="score-scale-card score-tone-possible">
                    <p class="score-scale-range">56–75</p>
                    <p class="score-scale-label">Possible Slowloris</p>
                </div>
                <div class="score-scale-card score-tone-strong">
                    <p class="score-scale-range">76–100</p>
                    <p class="score-scale-label">Strong Slowloris Indication</p>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-200 font-sans">
                <p class="font-semibold mb-1">Evidence Gating (penting)</p>
                <p class="leading-relaxed">
                    Skor di atas <strong>tidak otomatis</strong> menentukan keputusan akhir. Sebelum status menjadi
                    <strong>attack_detected</strong> dan keputusan menjadi <em>"Serangan asli"</em>, sistem memeriksa:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-0.5">
                    <li>HTTP harus dominan (rasio HTTP packets &ge; 10% atau total HTTP &ge; 50). Slowloris berbasis HTTP.</li>
                    <li>Minimal 2 dari 3 sinyal kuat: alert Snort relevan, koneksi long-lived, low-bandwidth + high-connection.</li>
                    <li>Skenario non-Slowloris (HTTP burst, iperf, portscan, baseline normal) hanya boleh sampai <strong>Suspicious</strong>.</li>
                    <li>Confidence AI tidak boleh menyulut <em>attack_detected</em> sendirian tanpa bukti Wireshark + Snort.</li>
                    <li>Pola portscan tidak pernah dilabeli Slowloris.</li>
                </ul>
                <p class="mt-2 text-amber-300/90">
                    Akibatnya, kategori <em>Possible Slowloris</em> di tabel skor di atas dipetakan ke
                    <code>experiment_status = suspicious</code>, bukan <code>attack_detected</code>.
                    Hanya <em>Strong Slowloris Indication</em> yang dipetakan ke <code>attack_detected</code> + "Serangan asli",
                    dan itu pun setelah lulus semua gate di atas.
                </p>
            </div>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Format JSON ke AI</p></div>
        <pre class="p-5 text-xs font-mono text-slate-300 overflow-x-auto bg-slate-950/80 border-t border-slate-800">{{ json_encode([
    'experiment_id' => 'EXP-001',
    'experiment_name' => 'Slowloris Lab Test Ubuntu Local',
    'traffic_type' => 'suspected_slowloris',
    'packet_summary' => [
        'total_packets' => 18420,
        'tcp_packets' => 17200,
        'http_packets' => 8200,
        'avg_packet_size' => 214,
        'duration_seconds' => 600,
    ],
    'connection_summary' => [
        'total_connections' => 1250,
        'long_lived_connections' => 870,
        'avg_connection_duration_seconds' => 145,
        'connections_to_http_port' => 1180,
        'throughput_kbps' => 44,
    ],
    'snort_alert_summary' => [
        'total_alerts' => 96,
        'high_severity_alerts' => 21,
        'medium_severity_alerts' => 58,
        'dominant_alert_type' => 'Possible Slow HTTP DoS Pattern',
    ],
    'baseline_summary' => [
        'normal_avg_connections' => 120,
        'normal_throughput_kbps' => 950,
        'normal_alert_count' => 2,
    ],
    'radar_score' => [
        'connection_duration_score' => 82,
        'header_anomaly_score' => 76,
        'low_bandwidth_high_connection_score' => 88,
        'snort_alert_score' => 91,
        'tcp_connection_score' => 79,
        'baseline_deviation_score' => 85,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Format JSON Respon AI</p></div>
        <pre class="p-5 text-xs font-mono text-slate-300 overflow-x-auto bg-slate-950/80 border-t border-slate-800">{{ json_encode([
    'model_name' => 'Groq Llama',
    'classification' => 'Slowloris Detected',
    'confidence_score' => 87,
    'reason' => 'Traffic menunjukkan banyak koneksi HTTP berdurasi panjang dengan throughput rendah dan alert Snort meningkat pada rentang waktu yang sama.',
    'supporting_indicators' => [
        'Long-lived HTTP connections',
        'Low bandwidth but high connection count',
        'Snort alert correlation',
        'Deviation from baseline traffic',
    ],
    'missing_evidence' => ['Raw HTTP header completion timing belum tersedia'],
    'recommendation' => 'Tambahkan fitur ekstraksi waktu antar-header dan bandingkan dengan baseline normal.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Evaluasi Akurasi & Confusion Matrix</p></div>
        <div class="p-5 text-sm text-slate-300 space-y-2">
            <p>Bila <strong>ground truth label</strong> tersedia pada eksperimen, sistem akan menghitung akurasi dengan membandingkan klasifikasi terhadap label aslinya. Metode evaluasi mencakup:</p>
            <ul class="list-disc list-inside text-slate-400 space-y-1">
                <li>Akurasi (TP+TN) / Total</li>
                <li>Precision = TP / (TP+FP)</li>
                <li>Recall = TP / (TP+FN)</li>
                <li>F1-score = 2 × Precision × Recall / (Precision + Recall)</li>
                <li>Confusion matrix sederhana antara label & klasifikasi sistem</li>
            </ul>
        </div>
    </div>
</div>

@endsection
