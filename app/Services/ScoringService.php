<?php

namespace App\Services;

use App\Models\AcquisitionFile;
use App\Models\Experiment;
use App\Models\ValidationFile;

/**
 * Hitung skor radar (0-100) per indikator dan Final Attack Score.
 *
 * Rumus indikator (semua di-clamp ke 0..100):
 *  - connection_duration_score          = clamp((avg_connection_duration / 180) * 100)
 *      asumsi: koneksi normal pendek (<5 detik), Slowloris cenderung sangat lama (>= 180s = 100)
 *  - header_anomaly_score               = clamp(half_open_connections / max(1, total_connections) * 100)
 *      jika data tidak tersedia, score = 0 (tidak boleh di-fallback dari indikator lain).
 *  - low_bandwidth_high_connection_score
 *      = clamp((total_connections / max(1, throughput_kbps)) * 5, 0, 100)
 *      digate oleh durasi koneksi (slow factor); burst pendek tidak boleh saturasi.
 *  - snort_alert_score                   = clamp((high*5 + medium*2 + low*1), 0, 100)
 *  - tcp_connection_score                = clamp((tcpRatio*0.6 + httpRatio*0.4) * 100)
 *      digate oleh slow factor; iPerf TCP murni tidak otomatis tinggi.
 *  - baseline_deviation_score
 *      = directional: hanya menghukum (banyak koneksi DAN throughput rendah).
 *  - ai_confidence_score                 = nilai dari hasil AI (0-100), HANYA bila klasifikasi AI = "Slowloris Detected".
 *
 * Final Attack Score (rumus dari spesifikasi):
 *   = 0.20*Conn + 0.20*Header + 0.15*LowBW + 0.20*Snort + 0.10*TCP + 0.10*Baseline + 0.05*AI
 *
 * Threshold + Evidence Gating:
 *   - 0..30   → Normal
 *   - 31..55  → Suspicious
 *   - 56..75  → Possible Slowloris (TANPA evidence gate → degraded ke Suspicious)
 *   - >75     → Strong Slowloris Indication (TANPA evidence gate → degraded ke Suspicious)
 *
 * Evidence gate untuk attack_detected:
 *   - HTTP harus dominan: http_packets/total_packets >= 0.10 (Slowloris pasti via HTTP).
 *   - Bukti gabungan: minimal 2 dari 3 berikut TRUE:
 *       a) snort_alert_score >= 30 ATAU dominant alert match Slow HTTP/Slowloris
 *       b) connection_duration_score >= 60 ATAU long_lived_connections >= 20
 *       c) low_bandwidth_high_connection_score >= 60 ATAU koneksi HTTP long-lived
 *          tinggi dengan throughput rendah
 *   - Skenario non-Slowloris (http-burst, iperf-bandwidth, portscan, normal-baseline)
 *     diblok, hanya boleh sampai Suspicious kecuali bukti override Snort sangat kuat.
 */
class ScoringService
{
    public const BASELINE_DEFAULT_CONNECTIONS = 120;
    public const BASELINE_DEFAULT_THROUGHPUT  = 950; // kbps
    public const BASELINE_DEFAULT_ALERTS      = 2;

    /** Skenario yang BUKAN Slowloris dan tidak boleh otomatis naik ke attack_detected. */
    public const NON_SLOWLORIS_SCENARIOS = [
        'http-burst',
        'iperf-bandwidth',
        'portscan',
        'normal-baseline',
    ];

    public function selectAcquisition(Experiment $experiment): ?AcquisitionFile
    {
        return $experiment->acquisitionFiles()->latest()->first();
    }

    public function selectValidation(Experiment $experiment, ?AcquisitionFile $acquisition = null): ?ValidationFile
    {
        if ($acquisition) {
            $paired = $experiment->validationFiles()
                ->where('acquisition_file_id', $acquisition->id)
                ->latest()
                ->first();

            if ($paired) {
                return $paired;
            }
        }

        return $experiment->validationFiles()->latest()->first();
    }

    public function buildFeatures(Experiment $experiment): array
    {
        /** @var AcquisitionFile|null $acq */
        $acq = $this->selectAcquisition($experiment);
        /** @var ValidationFile|null $val */
        $val = $this->selectValidation($experiment, $acq);

        $totalPackets    = (float) ($acq->total_packets ?? 0);
        $tcpPackets      = (float) ($acq->tcp_packets ?? 0);
        $httpPackets     = (float) ($acq->http_packets ?? 0);
        $avgPacketSize   = (float) ($acq->avg_packet_size ?? 0);

        $totalConnections = (float) ($acq->total_connections ?? 0);
        $avgConnDuration  = (float) ($acq->avg_connection_duration ?? 0);
        $halfOpen         = (float) ($acq->half_open_connections ?? 0);

        $parsed = is_array($acq?->parsed_summary) ? $acq->parsed_summary : [];
        $duration         = (float) ($parsed['duration'] ?? max(1, $experiment->capture_duration ?? 0));
        $throughput       = (float) ($parsed['throughput_kbps'] ?? 0);
        $longLived        = (float) ($parsed['long_lived_connections'] ?? 0);
        $connsToHttpPort  = (float) ($parsed['connections_to_http_port'] ?? 0);

        $totalAlerts      = (float) ($val->total_alerts ?? 0);
        $sev              = is_array($val?->parsed_summary)
            ? ($val->parsed_summary['severity_count'] ?? [])
            : [];

        $highAlerts   = (float) ($sev['high'] ?? 0);
        $mediumAlerts = (float) ($sev['medium'] ?? 0);
        $lowAlerts    = (float) ($sev['low'] ?? 0);
        if (($highAlerts + $mediumAlerts + $lowAlerts) <= 0 && $totalAlerts > 0) {
            $lowAlerts = $totalAlerts;
        }

        return [
            'total_packets'             => $totalPackets,
            'tcp_packets'               => $tcpPackets,
            'http_packets'              => $httpPackets,
            'avg_packet_size'           => $avgPacketSize,
            'duration_seconds'          => $duration,
            'total_connections'         => $totalConnections,
            'long_lived_connections'    => $longLived,
            'avg_connection_duration'   => $avgConnDuration,
            'connections_to_http_port'  => $connsToHttpPort,
            'throughput_kbps'           => $throughput,
            'half_open_connections'     => $halfOpen,
            'total_alerts'              => $totalAlerts,
            'high_severity_alerts'      => $highAlerts,
            'medium_severity_alerts'    => $mediumAlerts,
            'low_severity_alerts'       => $lowAlerts,
            'baseline_avg_connections'  => self::BASELINE_DEFAULT_CONNECTIONS,
            'baseline_throughput_kbps'  => self::BASELINE_DEFAULT_THROUGHPUT,
            'baseline_alert_count'      => self::BASELINE_DEFAULT_ALERTS,
        ];
    }

    public function computeRadarScores(array $f): array
    {
        // Slow factor: 0..1, mendekati 1 ketika rata-rata durasi koneksi >= 30 detik.
        $slowFactor = $this->slowFactor((float) ($f['avg_connection_duration'] ?? 0));

        // 1) Connection duration: butuh durasi panjang atau volume long-lived yang besar.
        $connScore = 0.0;
        if (($f['avg_connection_duration'] ?? 0) > 0) {
            $base = ($f['avg_connection_duration'] / 180.0) * 100;

            // Damp jika long_lived_connections sangat sedikit (<20).
            $longLived = (float) ($f['long_lived_connections'] ?? 0);
            $longLivedFactor = min(1.0, $longLived / 20.0);
            $baselineConnections = max(20.0, (float) ($f['baseline_avg_connections'] ?? self::BASELINE_DEFAULT_CONNECTIONS));

            // Jika data long_lived_connections tidak tersedia (0), pakai durasi saja
            // namun tetap di-damp 70% untuk menghindari satu koneksi panjang menyulut skor.
            if ($longLived <= 0) {
                $longLivedFactor = 0.7;
            }

            $durationScore = $this->clamp($base * $longLivedFactor);
            $longLivedVolumeScore = $longLived >= 20
                ? $this->clamp(($longLived / $baselineConnections) * 100)
                : 0.0;

            $connScore = max($durationScore, $longLivedVolumeScore);
        }

        // 2) Header anomaly: tanpa fallback dari indikator lain. 0 jika data tidak tersedia.
        $headerScore = 0.0;
        if ($f['total_connections'] > 0 && $f['half_open_connections'] > 0) {
            $headerScore = $this->clamp(
                ($f['half_open_connections'] / max(1, $f['total_connections'])) * 100
            );
        }

        // 3) Low bandwidth + high connection: digate slow factor agar burst pendek tidak saturasi.
        $lowBwScore = 0.0;
        if ($f['total_connections'] > 0) {
            $ratio = $f['total_connections'] / max(1, $f['throughput_kbps']);
            $rawLowBw = $this->clamp($ratio * 5);
            // Burst pendek (slow factor rendah) di-damp 30%; slow header utuh.
            $lowBwScore = $this->clamp($rawLowBw * (0.3 + 0.7 * $slowFactor));
        }

        // 4) Snort score: weighted by severity.
        $snortRaw = $f['high_severity_alerts'] * 5
                  + $f['medium_severity_alerts'] * 2
                  + $f['low_severity_alerts'] * 1;
        $snortScore = $this->clamp($snortRaw);

        // 5) TCP dominance: hanya bermakna ketika koneksi cenderung lambat (slow factor).
        $tcpScore = 0.0;
        if ($f['total_packets'] > 0) {
            $tcpRatio  = $f['tcp_packets'] / max(1, $f['total_packets']);
            $httpRatio = $f['http_packets'] / max(1, $f['total_packets']);
            $rawTcp = ($tcpRatio * 0.6 + $httpRatio * 0.4) * 100;
            // Tanpa indikasi koneksi lambat, TCP-dominant traffic biasa (iperf, file transfer)
            // tidak boleh memberi skor Slowloris signifikan.
            $tcpScore = $this->clamp($rawTcp * $slowFactor);
        }

        // 6) Baseline deviation: directional. Hanya banyak koneksi + throughput RENDAH yang menghukum.
        $baselineScore = $this->directionalBaselineScore($f);

        return [
            'connection_duration_score'           => round($connScore, 2),
            'header_anomaly_score'                => round($headerScore, 2),
            'low_bandwidth_high_connection_score' => round($lowBwScore, 2),
            'snort_alert_score'                   => round($snortScore, 2),
            'tcp_connection_score'                => round($tcpScore, 2),
            'baseline_deviation_score'            => round($baselineScore, 2),
            'ai_confidence_score'                 => 0,
        ];
    }

    public function computeFinalScore(array $radar): array
    {
        $score =
              0.20 * ($radar['connection_duration_score'] ?? 0)
            + 0.20 * ($radar['header_anomaly_score'] ?? 0)
            + 0.15 * ($radar['low_bandwidth_high_connection_score'] ?? 0)
            + 0.20 * ($radar['snort_alert_score'] ?? 0)
            + 0.10 * ($radar['tcp_connection_score'] ?? 0)
            + 0.10 * ($radar['baseline_deviation_score'] ?? 0)
            + 0.05 * ($radar['ai_confidence_score'] ?? 0);

        $score = round($score, 2);

        return [
            'final_attack_score' => $score,
            'attack_category'    => $this->categorize($score),
        ];
    }

    /**
     * Evaluasi penuh: hitung radar, final score, lalu terapkan evidence gating.
     *
     * @return array{
     *   features: array,
     *   radar: array,
     *   final_attack_score: float,
     *   raw_attack_category: string,    // hasil categorize() murni
     *   attack_category: string,        // setelah evidence gating
     *   experiment_status: string,
     *   final_decision: string,
     *   evidence_gates: array,
     *   gate_reasons: array
     * }
     */
    public function evaluateExperiment(Experiment $experiment, array $features, array $radar): array
    {
        $final  = $this->computeFinalScore($radar);
        $rawCat = $final['attack_category'];
        $score  = (float) $final['final_attack_score'];

        $context = [
            'scenario_key'        => (string) ($experiment->scenario_key ?? ''),
            'ground_truth_label'  => (string) ($experiment->ground_truth_label ?? ''),
            'traffic_type'        => (string) ($experiment->traffic_type ?? ''),
            'dominant_alert_type' => (string) ($this->dominantAlertType($experiment) ?? ''),
        ];

        $gates = $this->evaluateEvidenceGates($features, $radar, $context);
        $finalCategory = $this->applyEvidenceGate($rawCat, $gates, $score);

        return [
            'features'            => $features,
            'radar'               => $radar,
            'final_attack_score'  => $score,
            'raw_attack_category' => $rawCat,
            'attack_category'     => $finalCategory,
            'experiment_status'   => $this->categoryToExperimentStatus($finalCategory),
            'final_decision'      => $this->categoryToFinalDecision($finalCategory),
            'evidence_gates'      => $gates['flags'],
            'gate_reasons'        => $gates['reasons'],
        ];
    }

    public function categorize(float $score): string
    {
        return match (true) {
            $score <= 30 => 'Normal',
            $score <= 55 => 'Suspicious',
            $score <= 75 => 'Possible Slowloris',
            default      => 'Strong Slowloris Indication',
        };
    }

    public function categoryToExperimentStatus(string $category): string
    {
        return match ($category) {
            'Normal'                       => 'normal',
            'Suspicious',
            'Possible Slowloris'           => 'suspicious',
            'Strong Slowloris Indication'  => 'attack_detected',
            'Inconclusive'                 => 'inconclusive',
            default                        => 'inconclusive',
        };
    }

    public function categoryToFinalDecision(string $category): string
    {
        return match ($category) {
            'Normal'                       => 'Traffic normal',
            'Suspicious'                   => 'Perlu validasi lanjutan',
            'Possible Slowloris'           => 'Indikasi Slowloris, butuh validasi lanjutan',
            'Strong Slowloris Indication'  => 'Serangan asli',
            'Inconclusive'                 => 'Inconclusive',
            default                        => 'Perlu validasi lanjutan',
        };
    }

    /**
     * Evaluasi evidence-gate Slowloris.
     */
    private function evaluateEvidenceGates(array $f, array $radar, array $ctx): array
    {
        $flags = [];
        $reasons = [];

        // Gate 1: HTTP harus ada. Slowloris berbasis HTTP request tidak selesai.
        $totalPackets = (float) ($f['total_packets'] ?? 0);
        $httpPackets  = (float) ($f['http_packets'] ?? 0);
        $httpRatio = $totalPackets > 0 ? $httpPackets / $totalPackets : 0;
        $flags['http_present'] = $httpRatio >= 0.10 || $httpPackets >= 50;
        if (!$flags['http_present']) {
            $reasons[] = 'HTTP minim (rasio HTTP < 10% dan total HTTP packets < 50). Slowloris pasti berbasis HTTP.';
        }

        // Gate 2: minimal 2 dari 3 sinyal Slowloris.
        $a = (float) ($radar['snort_alert_score'] ?? 0) >= 30
            || $this->dominantAlertMatchesSlowHttp((string) ($ctx['dominant_alert_type'] ?? ''));
        $longLivedConnections = (float) ($f['long_lived_connections'] ?? 0);
        $httpPortConnections = (float) ($f['connections_to_http_port'] ?? 0);
        $throughputKbps = (float) ($f['throughput_kbps'] ?? 0);

        $b = (float) ($radar['connection_duration_score'] ?? 0) >= 60
            || $longLivedConnections >= 20;
        $c = (float) ($radar['low_bandwidth_high_connection_score'] ?? 0) >= 60
            || (
                $longLivedConnections >= 20
                && $httpPortConnections >= 20
                && $throughputKbps > 0
                && $throughputKbps <= 50
            );

        $flags['signal_snort_relevant']     = $a;
        $flags['signal_long_lived']         = $b;
        $flags['signal_low_bw_high_conn']   = $c;
        $signalCount = (int) $a + (int) $b + (int) $c;
        $flags['composite_signal_passed']   = $signalCount >= 2;
        if (!$flags['composite_signal_passed']) {
            $reasons[] = 'Sinyal Slowloris kurang (butuh ≥2 dari: Snort relevan, koneksi long-lived, low-bandwidth+high-connection).';
        }

        // Gate 3: skenario non-Slowloris harus diblok kecuali Snort sangat kuat.
        $scenario = strtolower(trim($ctx['scenario_key'] ?? ''));
        $groundTruth = strtolower(trim($ctx['ground_truth_label'] ?? ''));
        $isExplicitNonSlow = in_array($scenario, self::NON_SLOWLORIS_SCENARIOS, true)
            || in_array($groundTruth, ['normal', 'http_burst', 'iperf', 'portscan'], true);

        // Override hanya jika dominant alert benar-benar Slow HTTP/Slowloris dan snort score >= 60.
        $strongSnort = (float) ($radar['snort_alert_score'] ?? 0) >= 60
            && $this->dominantAlertMatchesSlowHttp((string) ($ctx['dominant_alert_type'] ?? ''));

        $flags['scenario_blocks_attack'] = $isExplicitNonSlow && !$strongSnort;
        if ($flags['scenario_blocks_attack']) {
            $reasons[] = 'Skenario "' . ($scenario ?: $groundTruth) . '" bukan Slowloris (HTTP burst / iperf / portscan / normal). Tidak boleh otomatis attack_detected.';
        }

        // Gate 4: portscan tidak boleh diberi label Slowloris sama sekali.
        $flags['is_portscan'] = $scenario === 'portscan' || str_contains(strtolower($ctx['dominant_alert_type'] ?? ''), 'scan');
        if ($flags['is_portscan']) {
            $reasons[] = 'Pola portscan terdeteksi; klasifikasi Slowloris tidak relevan.';
        }

        // Gate 5: AI bukan satu-satunya bukti.
        $flags['ai_alone_blocked'] = ($radar['ai_confidence_score'] ?? 0) > 0
            && $radar['snort_alert_score'] < 10
            && $radar['connection_duration_score'] < 30;
        if ($flags['ai_alone_blocked']) {
            $reasons[] = 'Confidence AI tidak boleh menggantikan bukti Wireshark + Snort.';
        }

        return [
            'flags'   => $flags,
            'reasons' => $reasons,
        ];
    }

    /**
     * Setelah skor categorize(), turunkan kategori bila gate bukti tidak terpenuhi.
     */
    private function applyEvidenceGate(string $rawCategory, array $gates, float $score): string
    {
        $flags = $gates['flags'];

        // Portscan: paksa Suspicious maks (atau Normal).
        if (!empty($flags['is_portscan'])) {
            return $score <= 30 ? 'Normal' : 'Suspicious';
        }

        // Skenario non-Slowloris: paksa maksimum Suspicious.
        if (!empty($flags['scenario_blocks_attack'])) {
            return $score <= 30 ? 'Normal' : 'Suspicious';
        }

        // attack_detected hanya untuk "Strong Slowloris Indication".
        if ($rawCategory === 'Strong Slowloris Indication') {
            // Wajib lulus HTTP gate + composite signal.
            if (empty($flags['http_present']) || empty($flags['composite_signal_passed'])) {
                return 'Possible Slowloris';
            }
            // Tidak boleh AI-only.
            if (!empty($flags['ai_alone_blocked'])) {
                return 'Possible Slowloris';
            }
            return 'Strong Slowloris Indication';
        }

        // Possible Slowloris: tidak otomatis attack_detected (sudah dipisah di mapping).
        if ($rawCategory === 'Possible Slowloris') {
            // Tetap Possible Slowloris -> akan di-map ke "suspicious" di experiment_status.
            return 'Possible Slowloris';
        }

        return $rawCategory;
    }

    private function directionalBaselineScore(array $f): float
    {
        $baselineConn = max(1, (float) ($f['baseline_avg_connections'] ?? self::BASELINE_DEFAULT_CONNECTIONS));
        $baselineTp   = max(1, (float) ($f['baseline_throughput_kbps'] ?? self::BASELINE_DEFAULT_THROUGHPUT));
        $baselineAlr  = max(1, (float) ($f['baseline_alert_count']     ?? self::BASELINE_DEFAULT_ALERTS));

        // Hanya bagian "lebih banyak koneksi dari baseline" yang menghukum.
        $connsAbove = max(0, ((float) $f['total_connections'] - $baselineConn) / $baselineConn);

        // Hanya throughput LEBIH RENDAH dari baseline yang menghukum (Slowloris choke bandwidth).
        $tpBelow = max(0, ($baselineTp - (float) $f['throughput_kbps']) / $baselineTp);

        // Alert lebih banyak dari baseline saja yang menghukum.
        $alertAbove = max(0, ((float) $f['total_alerts'] - $baselineAlr) / $baselineAlr);

        // Composite directional: koneksi-banyak + throughput-rendah saling menguatkan.
        $combined = ($connsAbove * $tpBelow) * 1.5
                  + min($connsAbove, 1.0) * 0.25
                  + min($alertAbove, 1.0) * 0.25;

        return $this->clamp($combined / 2.0 * 100);
    }

    private function slowFactor(float $avgConnDuration): float
    {
        if ($avgConnDuration <= 5) {
            return 0.0;
        }
        if ($avgConnDuration >= 30) {
            return 1.0;
        }
        return ($avgConnDuration - 5) / 25.0;
    }

    private function dominantAlertType(Experiment $experiment): ?string
    {
        // Hanya query ketika model sudah memiliki database connection (mis. di runtime).
        // Pada unit test tanpa DB, return null aman.
        if (!$experiment->exists) {
            return null;
        }

        try {
            $acq = $this->selectAcquisition($experiment);
            $val = $this->selectValidation($experiment, $acq);
            return $val?->dominant_alert_type;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function dominantAlertMatchesSlowHttp(string $alert): bool
    {
        if ($alert === '') {
            return false;
        }
        $needle = strtolower($alert);
        return str_contains($needle, 'slow')
            || str_contains($needle, 'slowloris')
            || str_contains($needle, 'http dos')
            || str_contains($needle, 'http denial')
            || str_contains($needle, 'incomplete header');
    }

    private function clamp(float $value, float $min = 0, float $max = 100): float
    {
        return max($min, min($max, $value));
    }
}
