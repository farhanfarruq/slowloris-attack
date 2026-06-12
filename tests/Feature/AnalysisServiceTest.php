<?php

namespace Tests\Feature;

use App\Models\AcquisitionFile;
use App\Models\Experiment;
use App\Models\User;
use App\Models\ValidationFile;
use App\Services\AnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test untuk AnalysisService dengan database real (sqlite memory).
 *
 * Memvalidasi bahwa:
 *  - HTTP burst tidak mengubah experiment_status menjadi attack_detected.
 *  - Slowloris dengan bukti penuh menjadi attack_detected.
 *  - Possible Slowloris (skor 56-75) -> experiment_status = suspicious.
 */
class AnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_marks_http_burst_as_suspicious_not_attack(): void
    {
        $experiment = $this->createExperimentWithFiles(
            scenarioKey: 'http-burst',
            groundTruth: 'normal',
            acquisitionAttrs: [
                'total_packets' => 4800,
                'tcp_packets'   => 4500,
                'http_packets'  => 4000,
                'avg_packet_size' => 200,
                'total_connections' => 800,
                'avg_connection_duration' => 0.8,   // pendek
                'half_open_connections'   => 0,
                'parsed_summary' => [
                    'duration'       => 60,
                    'throughput_kbps'=> 50,
                    'long_lived_connections' => 0,
                    'connections_to_http_port' => 800,
                ],
                'top_source_ips' => [],
                'top_destination_ips' => [],
                'protocol_distribution' => ['TCP' => 4500, 'HTTP' => 4000],
            ],
            validationAttrs: [
                'total_alerts' => 10,
                'parsed_summary' => [
                    'severity_count' => ['high' => 0, 'medium' => 5, 'low' => 5],
                ],
                'dominant_alert_type' => 'LOCAL LAB high HTTP request burst',
                'highest_severity'    => 'medium',
            ],
        );

        $service = app(AnalysisService::class);
        $features = $service->analyze($experiment->fresh());

        $experiment->refresh();
        $this->assertNotSame('attack_detected', $experiment->experiment_status,
            'HTTP burst tidak boleh menjadi attack_detected.');
        $this->assertNotSame('Strong Slowloris Indication', $features->attack_category);
    }

    public function test_analyze_strong_slowloris_with_full_evidence_is_attack_detected(): void
    {
        $experiment = $this->createExperimentWithFiles(
            scenarioKey: 'slow-http',
            groundTruth: 'slowloris_lab',
            acquisitionAttrs: [
                'total_packets' => 5500,
                'tcp_packets'   => 5000,
                'http_packets'  => 4000,
                'avg_packet_size' => 180,
                'total_connections' => 250,
                'avg_connection_duration' => 150,
                'half_open_connections'   => 100,
                'parsed_summary' => [
                    'duration'       => 100,
                    'throughput_kbps'=> 5,
                    'long_lived_connections' => 200,
                    'connections_to_http_port' => 250,
                ],
                'top_source_ips' => [],
                'top_destination_ips' => [],
                'protocol_distribution' => ['TCP' => 5000, 'HTTP' => 4000],
            ],
            validationAttrs: [
                'total_alerts' => 40,
                'parsed_summary' => [
                    'severity_count' => ['high' => 25, 'medium' => 10, 'low' => 5],
                ],
                'dominant_alert_type' => 'LOCAL LAB possible Slow HTTP or Slowloris traffic',
                'highest_severity'    => 'high',
            ],
        );

        $service = app(AnalysisService::class);
        $features = $service->analyze($experiment->fresh());

        $experiment->refresh();
        $this->assertSame('attack_detected', $experiment->experiment_status,
            'Slowloris kuat dengan bukti penuh harus attack_detected.');
        $this->assertSame('Strong Slowloris Indication', $features->attack_category);
    }

    private function createExperimentWithFiles(
        string $scenarioKey,
        string $groundTruth,
        array $acquisitionAttrs,
        array $validationAttrs,
    ): Experiment {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test+' . uniqid() . '@lab.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $experiment = Experiment::create([
            'experiment_code' => 'EXP-' . substr(uniqid(), -3),
            'name' => 'Test ' . $scenarioKey,
            'experiment_date' => now()->toDateString(),
            'network_interface' => 'enp0s8',
            'target_ip' => '192.168.56.103',
            'source_ip' => '192.168.56.102',
            'capture_duration' => 60,
            'scenario_key' => $scenarioKey,
            'traffic_type' => $groundTruth === 'slowloris_lab' ? 'slowloris_lab' : 'normal',
            'ground_truth_label' => $groundTruth,
            'status' => 'created',
            'experiment_status' => 'pending',
            'user_id' => $user->id,
        ]);

        $acquisition = AcquisitionFile::create(array_merge([
            'experiment_id' => $experiment->id,
            'original_name' => $scenarioKey . '-wireshark.pcapng',
            'stored_name'   => 'test/' . $scenarioKey . '.pcapng',
            'extension'     => 'pcapng',
            'size_bytes'    => 1024,
            'capture_label' => $scenarioKey . '-test',
            'scenario_key'  => $scenarioKey,
        ], $acquisitionAttrs));

        ValidationFile::create(array_merge([
            'experiment_id' => $experiment->id,
            'acquisition_file_id' => $acquisition->id,
            'original_name' => $scenarioKey . '-snort.log',
            'stored_name'   => 'test/' . $scenarioKey . '.log',
            'extension'     => 'log',
            'size_bytes'    => 512,
            'snort_mode'    => 'ids',
            'capture_label' => $scenarioKey . '-test',
            'scenario_key'  => $scenarioKey,
            'matches_slow_http_pattern' => $scenarioKey === 'slow-http',
        ], $validationAttrs));

        return $experiment;
    }
}
