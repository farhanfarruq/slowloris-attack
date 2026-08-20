<?php

namespace Tests\Unit;

use App\Services\AcquisitionParser;
use App\Models\Experiment;
use App\Services\ScoringService;
use App\Services\ValidationParser;
use Tests\TestCase;

require_once __DIR__.'/../../scripts/simulated-lab/generate-artifacts.php';

class SimulatedLabArtifactGeneratorTest extends TestCase
{
    public function test_offline_generator_writes_parseable_evidence_for_every_profile(): void
    {
        if (trim((string) shell_exec('command -v tshark')) === '') {
            $this->markTestSkipped('TShark tidak tersedia.');
        }

        $directory = sys_get_temp_dir().'/slowloris-simulated-'.bin2hex(random_bytes(4));
        $manifest = (new \OfflineLabArtifactGenerator())->generate($directory);

        try {
            $this->assertCount(10, $manifest['scenarios']);
            foreach ($manifest['scenarios'] as $key => $scenario) {
                $pcap = "{$directory}/{$key}-acquisition.pcapng";
                $log = "{$directory}/{$key}-validation.log";
                $acquisition = (new AcquisitionParser())->parse($pcap, 'pcapng');
                $validation = (new ValidationParser())->parse($log, 'log');

                $this->assertSame('tshark-fields-stream', $acquisition['parsed_summary']['parser']);
                $this->assertSame($scenario['packet_count'], $acquisition['total_packets']);
                $this->assertSame($scenario['alert_count'], $validation['total_alerts']);
            }

            $hping = (new AcquisitionParser())->parse("{$directory}/hping3-acquisition.pcapng", 'pcapng');
            $this->assertSame(1000, $hping['parsed_summary']['udp_packets']);
            $this->assertSame(800, $hping['parsed_summary']['icmp_packets']);

            $this->assertAttackProfilesReachDetected($directory, $manifest);
        } finally {
            foreach (glob("{$directory}/*") ?: [] as $path) {
                unlink($path);
            }
            rmdir($directory);
        }
    }

    private function assertAttackProfilesReachDetected(string $directory, array $manifest): void
    {
        $scoring = new ScoringService();
        foreach (['slowloris', 'loic', 'hoic', 'hping3', 'torshammer', 'xerxes'] as $key) {
            $scenario = $manifest['scenarios'][$key];
            $acquisition = (new AcquisitionParser())->parse("{$directory}/{$key}-acquisition.pcapng", 'pcapng');
            $validation = (new ValidationParser())->parse("{$directory}/{$key}-validation.log", 'log');
            $severity = $validation['severity_count'];
            $parsed = $acquisition['parsed_summary'];
            $features = [
                'total_packets' => $acquisition['total_packets'], 'tcp_packets' => $acquisition['tcp_packets'],
                'udp_packets' => $parsed['udp_packets'] ?? 0, 'icmp_packets' => $parsed['icmp_packets'] ?? 0,
                'http_packets' => $acquisition['http_packets'], 'avg_packet_size' => $acquisition['avg_packet_size'],
                'duration_seconds' => $parsed['duration'] ?? 0, 'total_connections' => $acquisition['total_connections'],
                'long_lived_connections' => $parsed['long_lived_connections'] ?? 0,
                'avg_connection_duration' => $acquisition['avg_connection_duration'],
                'connections_to_http_port' => $parsed['connections_to_http_port'] ?? 0,
                'throughput_kbps' => $parsed['throughput_kbps'] ?? 0, 'half_open_connections' => $acquisition['half_open_connections'],
                'total_alerts' => $validation['total_alerts'], 'high_severity_alerts' => $severity['high'] ?? 0,
                'medium_severity_alerts' => $severity['medium'] ?? 0, 'low_severity_alerts' => $severity['low'] ?? 0,
                'baseline_avg_connections' => ScoringService::BASELINE_DEFAULT_CONNECTIONS,
                'baseline_throughput_kbps' => ScoringService::BASELINE_DEFAULT_THROUGHPUT,
                'baseline_alert_count' => ScoringService::BASELINE_DEFAULT_ALERTS,
            ];
            $experiment = new Experiment();
            $experiment->forceFill([
                'scenario_key' => $key === 'slowloris' ? 'slow-http' : $key,
                'ground_truth_label' => $key === 'slowloris' ? 'slowloris' : $key,
                'traffic_type' => 'mixed', 'capture_duration' => 90,
                'tool_profile' => $scenario['tool_profile'], 'attack_pattern' => $scenario['attack_pattern'],
            ]);
            $radar = $scoring->computeRadarScores($features, $scenario['tool_profile']);
            $evaluation = $scoring->evaluateExperiment($experiment, $features, $radar, $scenario['tool_profile']);

            $this->assertSame('attack_detected', $evaluation['experiment_status'], $key);
        }
    }
}
