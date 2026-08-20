<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\User;
use App\Services\Esp32ArtifactImporter;
use App\Services\ExperimentEvidenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class Esp32ArtifactImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_persists_zero_alert_as_success_and_is_idempotent(): void
    {
        Storage::fake('local');
        config(['esp32.host' => '192.168.4.1', 'esp32.port' => 80]);

        $user = User::factory()->create();
        $experiment = $this->experiment($user, 'EXP-901', 'esp32');

        [$directory, $metadataPath] = $this->writeArtifacts('EXP-901');

        try {
            $result = app(Esp32ArtifactImporter::class)->import($metadataPath);
            $again = app(Esp32ArtifactImporter::class)->import($metadataPath);

            $this->assertFalse($result['already_imported']);
            $this->assertTrue($again['already_imported']);
            $this->assertSame(1, $experiment->acquisitionFiles()->count());
            $this->assertSame(1, $experiment->validationFiles()->count());
            $this->assertSame(0, $experiment->validationFiles()->first()->total_alerts);
            $this->assertTrue($experiment->fresh()->runtime_metadata['analysis_success']);
            $this->assertSame('validated', $experiment->fresh()->status);
            $bundle = app(ExperimentEvidenceService::class)->markdown($experiment->fresh());
            $this->assertStringContainsString('## ESP32 Runtime Metadata', $bundle);
            $this->assertStringContainsString($result['metadata']['pcap_sha256'], $bundle);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_import_refuses_to_relabel_vm_record(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->experiment($user, 'EXP-902', 'vm_ubuntu_server');
        [$directory, $metadataPath] = $this->writeArtifacts('EXP-902');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TARGET_PROVENANCE_MISMATCH');

        try {
            app(Esp32ArtifactImporter::class)->import($metadataPath);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function writeArtifacts(string $code): array
    {
        $directory = storage_path('framework/testing/esp32-import-'.$code);
        File::ensureDirectoryExists($directory);
        File::put($directory.'/capture.pcapng', 'test pcap fixture');
        File::put($directory.'/snort.log', 'Snort completed with zero alerts');
        File::put($directory.'/alert_fast.txt', '');

        $relative = fn (string $file) => ltrim(str_replace(base_path(), '', $file), DIRECTORY_SEPARATOR);
        $metadata = [
            'experiment_id' => $code,
            'target_type' => 'esp32',
            'target_ip' => '192.168.4.1',
            'target_port' => 80,
            'capture_interface' => 'wlp8s0',
            'pcap_file' => $relative($directory.'/capture.pcapng'),
            'pcap_sha256' => hash_file('sha256', $directory.'/capture.pcapng'),
            'capture_start' => '2026-08-20T10:00:00+07:00',
            'capture_end' => '2026-08-20T10:01:00+07:00',
            'packet_count' => null,
            'dropped_packets' => 0,
            'snort_exit_code' => 0,
            'snort_log' => $relative($directory.'/snort.log'),
            'snort_log_sha256' => hash_file('sha256', $directory.'/snort.log'),
            'alert_file' => $relative($directory.'/alert_fast.txt'),
            'alert_file_sha256' => hash_file('sha256', $directory.'/alert_fast.txt'),
            'alert_count' => 0,
            'analysis_success' => true,
            'esp32_metrics_before' => [
                'uptime_seconds' => 10,
                'free_heap_bytes' => 220000,
                'min_free_heap_bytes' => 215000,
                'request_count' => 1,
                'reset_reason' => 'POWERON',
            ],
            'esp32_metrics_after' => null,
        ];
        File::put($directory.'/metadata.json', json_encode($metadata, JSON_THROW_ON_ERROR));

        return [$directory, $relative($directory.'/metadata.json')];
    }

    private function experiment(User $user, string $code, string $target): Experiment
    {
        return Experiment::create([
            'experiment_code' => $code,
            'name' => 'ESP32 import test',
            'experiment_date' => now()->toDateString(),
            'scenario_key' => 'esp32-baseline',
            'traffic_type' => 'unknown',
            'status' => 'created',
            'experiment_status' => 'pending',
            'tool_profile' => 'slowloris',
            'attack_pattern' => 'slow_http',
            'analysis_profile_key' => 'slowloris',
            'target_platform' => $target,
            'user_id' => $user->id,
        ]);
    }
}
