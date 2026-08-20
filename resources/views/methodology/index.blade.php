@extends('layouts.app')

@section('title', 'Alur Sistem')
@section('subtitle', 'Pendekatan defensif multi-tool DDoS, scoring, AI Analysis, dan evaluasi.')

@section('content')

@php
    $toolProfiles = config('tool_profiles.profiles', []);
    $metricLabels = [
        'connection_duration_score' => 'Connection Duration Score',
        'header_anomaly_score' => 'Header Anomaly Score',
        'low_bandwidth_high_connection_score' => 'Low Bandwidth High Connection Score',
        'snort_alert_score' => 'Snort Alert Score',
        'tcp_connection_score' => 'TCP Connection Score',
        'baseline_deviation_score' => 'Baseline Deviation Score',
        'ai_confidence_score' => 'AI Confidence Score',
        'packet_volume_score' => 'Packet Volume Score',
        'connection_volume_score' => 'Connection Volume Score',
        'throughput_pressure_score' => 'Throughput Pressure Score',
        'http_volume_score' => 'HTTP Volume Score',
        'transport_flood_score' => 'Transport Flood Score',
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card">
        <div class="card-header"><p class="card-title">Tahapan Proses</p></div>
        <ol class="p-5 space-y-3 list-decimal list-inside text-sm text-slate-300">
            <li>Akuisisi data menggunakan Wireshark/dumpcap (.pcap, .pcapng) di interface lab.</li>
            <li>Validasi deteksi menggunakan Snort 3 (mode IDS/IPS) untuk membandingkan rule signature.</li>
            <li>Baseline traffic normal menggunakan iPerf3 dan browsing lokal ke web server lab.</li>
            <li>Testing defensif baru menggunakan ESP32 fisik sebagai target HTTP pada jaringan lab terisolasi.</li>
            <li>Tool profile penelitian dipisah: Slowloris, LOIC, HOIC, Hping3, Torshammer, dan Xerxes.</li>
            <li>Ekstraksi fitur traffic & alert ke dalam ringkasan numerik.</li>
            <li>AI Analysis multi-model (Groq, OpenAI-compatible, Gemini, Ollama) sebagai analis pembanding.</li>
            <li>Comparison hasil AI Analysis dengan scoring logic program dan evidence gate.</li>
            <li>Evaluasi akurasi: bandingkan klasifikasi sistem terhadap ground truth & alert IDS.</li>
        </ol>
    </div>

    <div class="card">
        <div class="card-header"><p class="card-title">Skala Skor Radar Chart (0–100)</p></div>
        <div class="p-5 text-sm text-slate-300 space-y-2">
            <p>Indikator yang dihitung pada saat ekstraksi fitur:</p>
            <ul class="list-disc list-inside text-slate-400 space-y-1">
                <li><span class="text-slate-200">Connection Duration Score</span> - durasi koneksi rata-rata vs ambang profil attack aktif.</li>
                <li><span class="text-slate-200">Header Anomaly Score</span> — proporsi koneksi half-open / header tidak selesai.</li>
                <li><span class="text-slate-200">Low Bandwidth High Connection Score</span> — banyak koneksi tetapi throughput rendah.</li>
                <li><span class="text-slate-200">Snort Alert Score</span> — bobot alert berdasarkan severity (high×5, med×2, low×1).</li>
                <li><span class="text-slate-200">TCP Connection Score</span> — dominasi paket TCP & HTTP.</li>
                <li><span class="text-slate-200">Baseline Deviation Score</span> — deviasi terhadap baseline iPerf3/browsing.</li>
                <li><span class="text-slate-200">AI Confidence Score</span> — rata-rata confidence multi-model AI.</li>
            </ul>
            <p class="mt-2">Semua skor dinormalisasi pada skala <strong>0-100</strong>. Weight dan gate dipilih berdasarkan tool profile, sehingga tiap alat dinilai memakai konteks profilnya sendiri.</p>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Final Attack Score Per Tool Profile</p></div>
        <div class="p-5 text-sm text-slate-300 space-y-3">
            <p>
                <strong>Final Attack Score</strong> adalah nama output umum. Rumus bobotnya mengikuti
                <code>tool_profile</code> aktif, sehingga Slowloris, LOIC, HOIC, Hping3, Torshammer,
                dan Xerxes tidak dinilai dengan formula yang sama.
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                @foreach ($toolProfiles as $profileKey => $profile)
                    <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-4">
                        <p class="font-semibold mb-2">{{ $profile['label'] ?? strtoupper($profileKey) }}</p>
                        <pre class="text-xs overflow-x-auto font-mono leading-relaxed">Final Attack Score =
@foreach (($profile['score_weights'] ?? []) as $metric => $weight)
{{ $loop->first ? '    ' : '  + ' }}{{ number_format((float) $weight, 2) }} × {{ $metricLabels[$metric] ?? \Illuminate\Support\Str::of($metric)->replace('_', ' ')->title() }}
@endforeach</pre>
                    </div>
                @endforeach
            </div>

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
                    <p class="score-scale-label">Possible Attack</p>
                </div>
                <div class="score-scale-card score-tone-strong">
                    <p class="score-scale-range">76–100</p>
                    <p class="score-scale-label">Attack Detected</p>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-xs font-sans">
                <p class="font-semibold mb-1">Evidence Gating (penting)</p>
                <p class="leading-relaxed">
                    Skor di atas <strong>tidak otomatis</strong> menentukan keputusan akhir. Sebelum status menjadi
                    <strong>attack_detected</strong>, sistem memeriksa gate yang sesuai dengan profile aktif:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-0.5">
                    <li>Slowloris dan Torshammer menekankan perilaku slow/incomplete request, koneksi long-lived, low-bandwidth, dan alert Snort relevan.</li>
                    <li>LOIC, HOIC, Hping3, dan Xerxes menekankan volume packet/koneksi/HTTP/transport sesuai pola profile masing-masing.</li>
                    <li>Skenario false positive seperti HTTP burst, iPerf, portscan, dan baseline normal hanya boleh sampai <strong>Suspicious</strong> atau kategori possible sesuai gate.</li>
                    <li>Confidence AI tidak boleh menyulut <em>attack_detected</em> sendirian tanpa bukti akuisisi dan validasi.</li>
                    <li>AI Analysis adalah pembanding; logic scoring berbasis evidence gate tetap sumber keputusan program.</li>
                    <li>Pola portscan tidak pernah dilabeli sebagai profile serangan lain.</li>
                </ul>
                <p class="mt-2">
                    Rentang skor tetap umum, tetapi label kategori mengikuti profile, misalnya
                    <em>Possible Slowloris</em>, <em>Possible LOIC</em>, atau <em>Possible Hping3</em>.
                    Status <code>attack_detected</code> hanya diberikan setelah kategori kuat lulus gate profile aktif.
                </p>
            </div>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Format JSON ke AI</p></div>
        <pre class="p-5 text-xs font-mono text-slate-300 overflow-x-auto bg-slate-950/80 border-t border-slate-800">{{ json_encode([
    'experiment_id' => 'EXP-001',
    'experiment_name' => 'ESP32 schema example - no measurement',
    'provenance' => 'schema_example_not_experiment_result',
    'tool_profile' => 'loic',
    'attack_pattern' => 'http_flood',
    'target_platform' => 'esp32',
    'traffic_type' => 'unknown',
    'packet_summary' => [
        'total_packets' => null,
        'tcp_packets' => null,
        'http_packets' => null,
        'avg_packet_size' => null,
        'duration_seconds' => null,
    ],
    'connection_summary' => [
        'total_connections' => null,
        'long_lived_connections' => null,
        'avg_connection_duration_seconds' => null,
        'connections_to_http_port' => null,
        'throughput_kbps' => null,
    ],
    'snort_alert_summary' => [
        'total_alerts' => null,
        'high_severity_alerts' => null,
        'medium_severity_alerts' => null,
        'dominant_alert_type' => null,
    ],
    'baseline_summary' => [
        'normal_avg_connections' => null,
        'normal_throughput_kbps' => null,
        'normal_alert_count' => null,
    ],
    'radar_score' => [
        'connection_duration_score' => null,
        'header_anomaly_score' => null,
        'low_bandwidth_high_connection_score' => null,
        'snort_alert_score' => null,
        'tcp_connection_score' => null,
        'baseline_deviation_score' => null,
    ],
    'logic_analysis' => [
        'classification' => 'Inconclusive',
        'score' => null,
        'gate_reasons' => ['Belum ada artefak eksperimen ESP32 pada contoh schema.'],
    ],
    'evidence_contract' => [
        'detected_allowed' => false,
        'detected_label' => 'Attack Detected',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Format JSON Respon AI</p></div>
        <pre class="p-5 text-xs font-mono text-slate-300 overflow-x-auto bg-slate-950/80 border-t border-slate-800">{{ json_encode([
    'model_name' => 'Groq Llama',
    'tool_profile' => 'loic',
    'attack_pattern' => 'http_flood',
    'classification' => 'Inconclusive',
    'confidence_score' => 0,
    'reason' => 'Contoh schema tidak memuat pengukuran ESP32 dan tidak boleh diklasifikasikan sebagai serangan.',
    'supporting_indicators' => [],
    'missing_evidence' => ['PCAP ESP32', 'hasil Snort', 'metrics before/after'],
    'logic_comparison' => [
        'logic_classification' => 'Inconclusive',
        'logic_score' => null,
        'agreement' => 'match',
    ],
    'chart_data' => [
        'confidence' => 0,
        'evidence_counts' => ['present' => 0, 'missing' => 3, 'blocking' => 1],
    ],
    'recommendation' => 'Jalankan eksperimen ESP32 nyata dan impor artefaknya sebelum analisis.',
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

<div class="card mt-4">
    <div class="card-header"><p class="card-title">Catatan Objek Penelitian</p></div>
    <div class="p-5 text-sm text-slate-300 space-y-2">
        <p>Aplikasi tidak mengarang data target. Eksperimen baru hanya disebut ESP32 setelah artefak host dan metadata runtime berhasil diimpor.</p>
        <p>Dataset lama tetap berlabel VM Ubuntu Server dan tidak pernah direlabel menjadi bukti ESP32.</p>
    </div>
</div>

@endsection
