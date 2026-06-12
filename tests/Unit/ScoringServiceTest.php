<?php

namespace Tests\Unit;

use App\Models\Experiment;
use App\Services\ScoringService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests untuk ScoringService.
 *
 * Verifikasi bahwa fix T1-05, T1-06, T1-11 (audit Slowloris Lab) berlaku:
 *  - Skor 56-75 (Possible Slowloris) TIDAK otomatis attack_detected.
 *  - Tanpa bukti gabungan (Snort + long-lived + low-bw), skor tinggi diturunkan.
 *  - Skenario portscan, http-burst, iperf-bandwidth tidak boleh attack_detected.
 */
class ScoringServiceTest extends TestCase
{
    private ScoringService $scoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoring = new ScoringService();
    }

    /** Skor 60 dengan 0 alert tidak boleh menjadi attack_detected. */
    public function test_borderline_score_without_snort_is_not_attack_detected(): void
    {
        $experiment = $this->fakeExperiment(scenarioKey: 'slow-http', groundTruth: 'slowloris_lab');

        $features = $this->baseFeatures([
            'avg_connection_duration' => 90,   // sudah long-lived
            'long_lived_connections'  => 25,
            'total_connections'       => 30,
            'throughput_kbps'         => 5,
            'tcp_packets'             => 800,
            'http_packets'            => 600,
            'total_packets'           => 1000,
            // Snort kosong
            'high_severity_alerts'    => 0,
            'medium_severity_alerts'  => 0,
            'low_severity_alerts'     => 0,
            'total_alerts'            => 0,
        ]);

        $radar = $this->scoring->computeRadarScores($features);
        $eval  = $this->scoring->evaluateExperiment($experiment, $features, $radar);

        $this->assertNotSame('attack_detected', $eval['experiment_status'],
            'Tanpa bukti Snort, skor borderline tidak boleh attack_detected.');
    }

    /** Strong Slowloris dengan bukti lengkap harus tetap attack_detected. */
    public function test_strong_slowloris_with_full_evidence_remains_attack_detected(): void
    {
        $experiment = $this->fakeExperiment(scenarioKey: 'slow-http', groundTruth: 'slowloris_lab');

        $features = $this->baseFeatures([
            'avg_connection_duration' => 150,
            'long_lived_connections'  => 200,
            'total_connections'       => 250,
            'half_open_connections'   => 100,
            'throughput_kbps'         => 5,
            'tcp_packets'             => 5000,
            'http_packets'            => 4000,
            'total_packets'           => 5500,
            'high_severity_alerts'    => 25,
            'medium_severity_alerts'  => 10,
            'low_severity_alerts'     => 5,
            'total_alerts'            => 40,
        ]);

        $radar = $this->scoring->computeRadarScores($features);
        $eval  = $this->scoring->evaluateExperiment($experiment, $features, $radar);

        $this->assertSame('attack_detected', $eval['experiment_status']);
        $this->assertSame('Serangan asli', $eval['final_decision']);
    }

    /** HTTP Burst: koneksi banyak tapi pendek tidak boleh dianggap Slowloris. */
    public function test_http_burst_scenario_is_blocked_from_attack_detected(): void
    {
        $experiment = $this->fakeExperiment(scenarioKey: 'http-burst', groundTruth: 'normal');

        $features = $this->baseFeatures([
            'avg_connection_duration' => 0.8,   // pendek
            'long_lived_connections'  => 0,
            'total_connections'       => 800,   // banyak
            'throughput_kbps'         => 50,
            'tcp_packets'             => 4500,
            'http_packets'            => 4000,
            'total_packets'           => 4800,
            'high_severity_alerts'    => 0,
            'medium_severity_alerts'  => 5,
            'low_severity_alerts'     => 5,
            'total_alerts'            => 10,
        ]);

        $radar = $this->scoring->computeRadarScores($features);
        $eval  = $this->scoring->evaluateExperiment($experiment, $features, $radar);

        $this->assertNotSame('attack_detected', $eval['experiment_status'],
            'HTTP Burst tidak boleh otomatis attack_detected.');
        $this->assertNotSame('Serangan asli', $eval['final_decision']);
    }

    /** iPerf3: throughput tinggi murni TCP tidak boleh dianggap Slowloris. */
    public function test_iperf_bandwidth_scenario_is_blocked_from_attack_detected(): void
    {
        $experiment = $this->fakeExperiment(scenarioKey: 'iperf-bandwidth', groundTruth: 'normal');

        $features = $this->baseFeatures([
            'avg_connection_duration' => 10,
            'long_lived_connections'  => 0,
            'total_connections'       => 4,
            'throughput_kbps'         => 80000,  // sangat tinggi
            'tcp_packets'             => 40000,
            'http_packets'            => 0,      // bukan HTTP
            'total_packets'           => 40000,
            'high_severity_alerts'    => 0,
            'medium_severity_alerts'  => 0,
            'low_severity_alerts'     => 0,
            'total_alerts'            => 0,
        ]);

        $radar = $this->scoring->computeRadarScores($features);
        $eval  = $this->scoring->evaluateExperiment($experiment, $features, $radar);

        $this->assertNotSame('attack_detected', $eval['experiment_status']);
        $this->assertNotSame('Serangan asli', $eval['final_decision']);
        // Throughput jauh di atas baseline tidak menambah skor Slowloris.
        $this->assertLessThan(40, (float) $radar['baseline_deviation_score']);
    }

    /** Portscan: tidak boleh diberi label Slowloris sama sekali. */
    public function test_portscan_scenario_is_never_slowloris(): void
    {
        $experiment = $this->fakeExperiment(scenarioKey: 'portscan', groundTruth: 'portscan');

        $features = $this->baseFeatures([
            'avg_connection_duration' => 0.2,
            'long_lived_connections'  => 0,
            'total_connections'       => 1500,
            'throughput_kbps'         => 30,
            'tcp_packets'             => 3000,
            'http_packets'            => 0,
            'total_packets'           => 3100,
            'high_severity_alerts'    => 0,
            'medium_severity_alerts'  => 50,
            'low_severity_alerts'     => 50,
            'total_alerts'            => 100,
        ]);

        $radar = $this->scoring->computeRadarScores($features);
        $eval  = $this->scoring->evaluateExperiment($experiment, $features, $radar);

        $this->assertNotSame('attack_detected', $eval['experiment_status']);
        $this->assertStringNotContainsString('Slowloris', $eval['attack_category']);
    }

    /** AI confidence sendirian tidak boleh menyulut attack_detected. */
    public function test_ai_only_evidence_is_blocked(): void
    {
        $experiment = $this->fakeExperiment(scenarioKey: 'slow-http', groundTruth: 'slowloris_lab');

        $features = $this->baseFeatures([
            'avg_connection_duration' => 1,
            'long_lived_connections'  => 0,
            'total_connections'       => 5,
            'throughput_kbps'         => 800,
            'tcp_packets'             => 100,
            'http_packets'            => 50,
            'total_packets'           => 150,
            'high_severity_alerts'    => 0,
            'medium_severity_alerts'  => 0,
            'low_severity_alerts'     => 0,
            'total_alerts'            => 0,
        ]);

        $radar = $this->scoring->computeRadarScores($features);
        $radar['ai_confidence_score'] = 99;   // AI 99% yakin Slowloris

        $eval = $this->scoring->evaluateExperiment($experiment, $features, $radar);

        $this->assertNotSame('attack_detected', $eval['experiment_status'],
            'AI tidak boleh menyulut attack_detected sendirian.');
    }

    /** Possible Slowloris (skor 56-75) tidak otomatis "Serangan asli". */
    public function test_possible_slowloris_does_not_become_serangan_asli(): void
    {
        $this->assertSame('suspicious',
            $this->scoring->categoryToExperimentStatus('Possible Slowloris'));
        $this->assertSame('Indikasi Slowloris, butuh validasi lanjutan',
            $this->scoring->categoryToFinalDecision('Possible Slowloris'));
    }

    /** Strong Slowloris menghasilkan "Serangan asli". */
    public function test_strong_slowloris_maps_to_serangan_asli(): void
    {
        $this->assertSame('attack_detected',
            $this->scoring->categoryToExperimentStatus('Strong Slowloris Indication'));
        $this->assertSame('Serangan asli',
            $this->scoring->categoryToFinalDecision('Strong Slowloris Indication'));
    }

    /** Slow factor di-gate: durasi koneksi < 5 detik tidak boleh saturasi. */
    public function test_short_connections_do_not_saturate_low_bw_score(): void
    {
        $f = $this->baseFeatures([
            'avg_connection_duration' => 0.3,
            'total_connections'       => 1000,
            'throughput_kbps'         => 10,
            'tcp_packets'             => 5000,
            'http_packets'            => 4000,
            'total_packets'           => 5000,
        ]);

        $radar = $this->scoring->computeRadarScores($f);

        $this->assertLessThan(60, (float) $radar['low_bandwidth_high_connection_score'],
            'Burst pendek tidak boleh saturasi low_bw_high_conn score.');
        $this->assertLessThan(20, (float) $radar['tcp_connection_score'],
            'TCP-dominant tanpa long-lived tidak boleh saturasi tcp_score.');
    }

    private function fakeExperiment(string $scenarioKey, string $groundTruth): Experiment
    {
        $experiment = new Experiment();
        $experiment->forceFill([
            'scenario_key'       => $scenarioKey,
            'ground_truth_label' => $groundTruth,
            'traffic_type'       => $groundTruth === 'slowloris_lab' ? 'slowloris_lab' : 'normal',
            'capture_duration'   => 60,
        ]);
        return $experiment;
    }

    private function baseFeatures(array $overrides = []): array
    {
        return array_merge([
            'total_packets'             => 0,
            'tcp_packets'               => 0,
            'http_packets'              => 0,
            'avg_packet_size'           => 0,
            'duration_seconds'          => 60,
            'total_connections'         => 0,
            'long_lived_connections'    => 0,
            'avg_connection_duration'   => 0,
            'connections_to_http_port'  => 0,
            'throughput_kbps'           => 0,
            'half_open_connections'     => 0,
            'total_alerts'              => 0,
            'high_severity_alerts'      => 0,
            'medium_severity_alerts'    => 0,
            'low_severity_alerts'       => 0,
            'baseline_avg_connections'  => ScoringService::BASELINE_DEFAULT_CONNECTIONS,
            'baseline_throughput_kbps'  => ScoringService::BASELINE_DEFAULT_THROUGHPUT,
            'baseline_alert_count'      => ScoringService::BASELINE_DEFAULT_ALERTS,
        ], $overrides);
    }
}
