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
            <li>Testing defensif dilakukan pada target VM Ubuntu Server di jaringan lab terisolasi.</li>
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
    'experiment_name' => 'Attack Profile Lab Test Ubuntu Local',
    'tool_profile' => 'loic',
    'attack_pattern' => 'http_flood',
    'target_platform' => 'vm_ubuntu_server',
    'traffic_type' => 'suspected_attack',
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
    'logic_analysis' => [
        'classification' => 'Attack Detected',
        'score' => 88.4,
        'gate_reasons' => [],
    ],
    'evidence_contract' => [
        'detected_allowed' => true,
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
    'classification' => 'Attack Detected',
    'confidence_score' => 87,
    'reason' => 'Traffic menunjukkan banyak koneksi HTTP berdurasi panjang dengan throughput rendah dan alert Snort meningkat pada rentang waktu yang sama.',
    'supporting_indicators' => [
        'Long-lived HTTP connections',
        'Low bandwidth but high connection count',
        'Snort alert correlation',
        'Deviation from baseline traffic',
    ],
    'missing_evidence' => ['Raw HTTP header completion timing belum tersedia'],
    'logic_comparison' => [
        'logic_classification' => 'Attack Detected',
        'logic_score' => 88.4,
        'agreement' => 'match',
    ],
    'chart_data' => [
        'confidence' => 87,
        'evidence_counts' => ['present' => 4, 'missing' => 1, 'blocking' => 0],
    ],
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

<div class="card mt-4">
    <div class="card-header"><p class="card-title">Catatan Objek Penelitian</p></div>
    <div class="p-5 text-sm text-slate-300 space-y-2">
        <p>Aplikasi tidak mengarang data target. Selama dataset lab masih berasal dari VM Ubuntu Server, field target platform disimpan sebagai VM Ubuntu Server.</p>
        <p>Narasi laporan akhir boleh menjelaskan objek penelitian berupa ESP32/IoT drone hanya jika skenario pengambilan data dan metadata eksperimen memang memuat konteks tersebut.</p>
    </div>
</div>

@endsection
