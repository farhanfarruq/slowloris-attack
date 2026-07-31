<?php

namespace Tests\Feature;

use App\Models\AcquisitionFile;
use App\Models\AiResult;
use App\Models\Experiment;
use App\Models\ExtractedFeature;
use App\Models\SnortAlert;
use App\Models\User;
use App\Models\ValidationFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExperimentEvidenceBundleTest extends TestCase
{
    use RefreshDatabase;

    public function test_experiment_detail_shows_evidence_drilldown(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user);
        $this->attachEvidence($experiment);

        $this->actingAs($user)
            ->get(route('experiments.show', $experiment))
            ->assertOk()
            ->assertSee('Evidence Drilldown')
            ->assertSee('Gate Passed')
            ->assertSee('profile_signal_present')
            ->assertSee('Snort Evidence')
            ->assertSee('AI Validation');
    }

    public function test_evidence_bundle_command_writes_markdown_file(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user);
        $this->attachEvidence($experiment);
        $path = storage_path('app/testing/evidence-bundle.md');

        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('lab:evidence-bundle', [
            'experiment' => $experiment->experiment_code,
            '--output' => $path,
        ]));

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('# Evidence Bundle EVID-001', $content);
        $this->assertStringContainsString('## Evidence Gates', $content);
        $this->assertStringContainsString('profile_signal_present', $content);
        $this->assertStringContainsString('## AI Validation', $content);
        $this->assertStringNotContainsString('raw_request', $content);
    }

    public function test_evidence_bundle_route_downloads_markdown(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user);
        $this->attachEvidence($experiment);

        $this->actingAs($user)
            ->get(route('experiments.evidence-bundle', $experiment))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertSee('# Evidence Bundle EVID-001')
            ->assertDontSee('raw_request');
    }

    private function experiment(User $user): Experiment
    {
        return Experiment::create([
            'experiment_code' => 'EVID-001',
            'name' => 'Evidence bundle sample',
            'experiment_date' => now()->toDateString(),
            'network_interface' => 'eth0',
            'target_ip' => '10.0.0.10',
            'source_ip' => '10.0.0.2',
            'capture_duration' => 60,
            'scenario_key' => 'slow-http-lab',
            'traffic_type' => 'mixed',
            'status' => 'completed',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'slowloris',
            'tool_profile' => 'slowloris',
            'attack_pattern' => 'slow_http',
            'analysis_profile_key' => 'slowloris',
            'target_platform' => 'vm_ubuntu_server',
            'user_id' => $user->id,
        ]);
    }

    private function attachEvidence(Experiment $experiment): void
    {
        AcquisitionFile::create([
            'experiment_id' => $experiment->id,
            'original_name' => 'slow-http.pcapng',
            'stored_name' => 'tests/slow-http.pcapng',
            'extension' => 'pcapng',
            'size_bytes' => 100,
            'total_packets' => 1200,
            'tcp_packets' => 1100,
            'http_packets' => 900,
            'total_connections' => 80,
        ]);

        $validation = ValidationFile::create([
            'experiment_id' => $experiment->id,
            'original_name' => 'slow-http-snort.log',
            'stored_name' => 'tests/slow-http-snort.log',
            'extension' => 'log',
            'size_bytes' => 100,
            'total_alerts' => 4,
            'highest_severity' => 'high',
        ]);

        SnortAlert::create([
            'experiment_id' => $experiment->id,
            'validation_file_id' => $validation->id,
            'severity' => 'high',
            'source_ip' => '10.0.0.2',
            'destination_ip' => '10.0.0.10',
            'destination_port' => 80,
            'protocol' => 'tcp',
            'message' => 'Slow HTTP test alert',
        ]);

        ExtractedFeature::create([
            'experiment_id' => $experiment->id,
            'final_attack_score' => 91,
            'attack_category' => 'Attack Detected',
            'raw_features' => [
                'tool_profile' => 'slowloris',
                'logic_classification' => 'Slowloris Detected',
                'final_decision' => 'attack_detected',
                'evidence_gates' => [
                    'profile_signal_present' => true,
                    'false_positive_guard_clear' => true,
                    'snort_evidence_present' => true,
                ],
                'gate_reasons' => ['profile_signal_present passed', 'Snort evidence present'],
                'missing_evidence' => [],
            ],
        ]);

        AiResult::create([
            'experiment_id' => $experiment->id,
            'model_name' => 'test-model',
            'classification' => 'Slowloris Detected',
            'confidence_score' => 88,
            'reason' => 'Evidence matches payload fields.',
            'raw_request' => ['secret_should_not_export' => true],
        ]);
    }
}
