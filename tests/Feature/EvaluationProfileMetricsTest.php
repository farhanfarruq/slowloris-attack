<?php

namespace Tests\Feature;

use App\Models\AcquisitionFile;
use App\Models\AiResult;
use App\Models\AuditLog;
use App\Models\CalibrationSnapshot;
use App\Models\Experiment;
use App\Models\ExtractedFeature;
use App\Models\User;
use App\Models\ValidationFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EvaluationProfileMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_tool_profile_detection_is_profile_mismatch_not_true_positive(): void
    {
        $user = User::factory()->create();

        $experiment = Experiment::create([
            'experiment_code' => 'EVAL-PM-001',
            'name' => 'LOIC ground truth analyzed as Hping3',
            'experiment_date' => now()->toDateString(),
            'network_interface' => 'eth0',
            'target_ip' => '10.0.0.10',
            'source_ip' => '10.0.0.2',
            'capture_duration' => 60,
            'scenario_key' => 'http-flood-lab',
            'traffic_type' => 'mixed',
            'status' => 'completed',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'loic',
            'tool_profile' => 'hping3',
            'attack_pattern' => 'tcp_syn_flood',
            'analysis_profile_key' => 'hping3',
            'target_platform' => 'vm_ubuntu_server',
            'user_id' => $user->id,
        ]);

        ExtractedFeature::create([
            'experiment_id' => $experiment->id,
            'final_attack_score' => 91,
            'attack_category' => 'Strong Hping3 Indication',
            'raw_features' => [
                'tool_profile' => 'hping3',
                'logic_classification' => 'Strong Hping3 Indication',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertSee('Profile Mismatch')
            ->assertSee('PM')
            ->assertSee('0<span class="text-base">%</span>', false);
    }

    public function test_local_profile_evaluation_command_outputs_json_metrics(): void
    {
        $user = User::factory()->create();

        Experiment::create([
            'experiment_code' => 'EVAL-CLI-001',
            'name' => 'LOIC ground truth analyzed as Hping3',
            'experiment_date' => now()->toDateString(),
            'network_interface' => 'eth0',
            'target_ip' => '10.0.0.10',
            'source_ip' => '10.0.0.2',
            'capture_duration' => 60,
            'scenario_key' => 'http-flood-lab',
            'traffic_type' => 'mixed',
            'status' => 'completed',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'loic',
            'tool_profile' => 'hping3',
            'attack_pattern' => 'tcp_syn_flood',
            'analysis_profile_key' => 'hping3',
            'target_platform' => 'vm_ubuntu_server',
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, Artisan::call('lab:evaluate-profiles', ['--json' => true]));

        $output = Artisan::output();

        $this->assertStringContainsString('"profileAware"', $output);
        $this->assertStringContainsString('"pm": 1', $output);
        $this->assertStringContainsString('"key": "loic"', $output);
        $this->assertStringContainsString('"fn": 1', $output);
        $this->assertStringContainsString('"key": "hping3"', $output);
        $this->assertStringContainsString('"fp": 1', $output);
    }

    public function test_evaluation_page_shows_dataset_coverage_and_quality_status(): void
    {
        $user = User::factory()->create();

        $attack = $this->experiment($user, [
            'experiment_code' => 'EVAL-COV-001',
            'name' => 'Slowloris controlled sample',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'slowloris',
            'tool_profile' => 'slowloris',
        ]);
        $this->attachEvidence($attack, withValidation: true);

        $normal = $this->experiment($user, [
            'experiment_code' => 'EVAL-COV-002',
            'name' => 'Baseline normal sample',
            'experiment_status' => 'normal',
            'ground_truth_label' => 'normal',
            'tool_profile' => 'slowloris',
            'scenario_key' => 'baseline-normal',
            'traffic_type' => 'normal',
        ]);
        $this->attachEvidence($normal, withValidation: false);

        $this->actingAs($user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertSee('Dataset Coverage Per Profile')
            ->assertSee('Slowloris')
            ->assertSee('Ready')
            ->assertSee('Quality');
    }

    public function test_local_profile_evaluation_command_outputs_csv_metrics(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user, ['experiment_code' => 'EVAL-CSV-001']);
        $this->attachEvidence($experiment, withValidation: true);

        $this->assertSame(0, Artisan::call('lab:evaluate-profiles', ['--format' => 'csv']));

        $output = Artisan::output();

        $this->assertStringContainsString('code,name,profile,actual,predicted,binary_type,profile_type,final_score,category', $output);
        $this->assertStringContainsString('EVAL-CSV-001', $output);
    }

    public function test_evaluation_export_route_downloads_csv(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user, ['experiment_code' => 'EVAL-WEB-001']);
        $this->attachEvidence($experiment, withValidation: true);

        $this->actingAs($user)
            ->get(route('evaluation.export', 'csv'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertSee('code,name,profile,actual,predicted,binary_type,profile_type,final_score,category')
            ->assertSee('EVAL-WEB-001');
    }

    public function test_evaluation_page_shows_false_positive_guard_and_ai_disagreement(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user, [
            'experiment_code' => 'EVAL-GUARD-001',
            'name' => 'HTTP burst false positive sample',
            'scenario_key' => 'http-burst',
            'traffic_type' => 'normal',
            'experiment_status' => 'normal',
            'ground_truth_label' => 'normal',
            'tool_profile' => 'slowloris',
        ]);
        $this->attachEvidence($experiment, withValidation: false);

        AiResult::create([
            'experiment_id' => $experiment->id,
            'model_name' => 'test-ai',
            'classification' => 'Slowloris Detected',
            'confidence_score' => 92,
            'reason' => 'over-detect sample',
        ]);

        $this->actingAs($user)
            ->get(route('evaluation.index'))
            ->assertOk()
            ->assertSee('False Positive Guard')
            ->assertSee('HTTP Burst')
            ->assertSee('AI Disagreement Queue')
            ->assertSee('AI over-detect');
    }

    public function test_profile_calibration_command_simulates_without_changing_data(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user, [
            'experiment_code' => 'EVAL-CAL-001',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'slowloris',
            'tool_profile' => 'slowloris',
        ]);
        $this->attachEvidence($experiment, withValidation: true);

        $this->assertSame(0, Artisan::call('lab:calibrate-profile', [
            'profile' => 'slowloris',
            '--detected' => 95,
            '--suspicious' => 56,
        ]));

        $output = Artisan::output();
        $this->assertStringContainsString('Calibration simulation only', $output);
        $this->assertStringContainsString('EVAL-CAL-001', $output);
        $this->assertSame('attack_detected', $experiment->fresh()->experiment_status);
    }

    public function test_evaluation_page_shows_calibration_simulation(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user, [
            'experiment_code' => 'EVAL-WEB-CAL-001',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'slowloris',
            'tool_profile' => 'slowloris',
        ]);
        $this->attachEvidence($experiment, withValidation: true);

        $this->actingAs($user)
            ->get(route('evaluation.index', [
                'calibration_profile' => 'slowloris',
                'detected' => 95,
                'suspicious' => 56,
            ]))
            ->assertOk()
            ->assertSee('Profile Calibration Review')
            ->assertSee('Simulasi ini tidak mengubah status eksperimen asli')
            ->assertSee('EVAL-WEB-CAL-001')
            ->assertSee('attack_detected')
            ->assertSee('suspicious');

        $this->assertSame('attack_detected', $experiment->fresh()->experiment_status);
    }

    public function test_calibration_export_route_downloads_snapshot(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user, [
            'experiment_code' => 'EVAL-CAL-EXPORT-001',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'slowloris',
            'tool_profile' => 'slowloris',
        ]);
        $this->attachEvidence($experiment, withValidation: true);

        $this->actingAs($user)
            ->get(route('evaluation.calibration.export', [
                'format' => 'md',
                'calibration_profile' => 'slowloris',
                'detected' => 95,
                'suspicious' => 56,
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertSee('# Profile Calibration Snapshot')
            ->assertSee('EVAL-CAL-EXPORT-001')
            ->assertSee('snapshot ini hanya simulasi');
    }

    public function test_profile_calibration_command_exports_snapshot_file(): void
    {
        $user = User::factory()->create();
        $experiment = $this->experiment($user, [
            'experiment_code' => 'EVAL-CAL-FILE-001',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'slowloris',
            'tool_profile' => 'slowloris',
        ]);
        $this->attachEvidence($experiment, withValidation: true);
        $path = storage_path('app/testing/calibration-snapshot.md');

        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('lab:calibrate-profile', [
            'profile' => 'slowloris',
            '--detected' => 95,
            '--suspicious' => 56,
            '--format' => 'md',
            '--output' => $path,
        ]));

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('# Profile Calibration Snapshot', $content);
        $this->assertStringContainsString('EVAL-CAL-FILE-001', $content);
        $this->assertSame('attack_detected', $experiment->fresh()->experiment_status);
        unlink($path);
    }

    public function test_admin_can_save_calibration_snapshot_without_changing_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $experiment = $this->experiment($admin, [
            'experiment_code' => 'EVAL-CAL-SAVE-001',
            'experiment_status' => 'attack_detected',
            'ground_truth_label' => 'slowloris',
            'tool_profile' => 'slowloris',
        ]);
        $this->attachEvidence($experiment, withValidation: true);

        $this->actingAs($admin)
            ->post(route('evaluation.calibration.snapshots.store'), [
                'calibration_profile' => 'slowloris',
                'detected' => 95,
                'suspicious' => 56,
            ])
            ->assertRedirect(route('evaluation.index', [
                'calibration_profile' => 'slowloris',
                'detected' => 95,
                'suspicious' => 56,
            ]));

        $this->assertDatabaseHas('calibration_snapshots', [
            'tool_profile' => 'slowloris',
            'detected_threshold' => 95,
            'suspicious_threshold' => 56,
            'user_id' => $admin->id,
        ]);
        $this->assertSame('attack_detected', $experiment->fresh()->experiment_status);
        $this->assertTrue(AuditLog::where('action', 'calibration.snapshot_saved')->exists());
        $this->assertSame(1, CalibrationSnapshot::count());
    }

    public function test_viewer_cannot_save_calibration_snapshot(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->post(route('evaluation.calibration.snapshots.store'), [
                'calibration_profile' => 'slowloris',
                'detected' => 95,
                'suspicious' => 56,
            ])
            ->assertForbidden();

        $this->assertSame(0, CalibrationSnapshot::count());
    }

    private function experiment(User $user, array $overrides = []): Experiment
    {
        return Experiment::create(array_merge([
            'experiment_code' => 'EVAL-BASE-001',
            'name' => 'Evaluation sample',
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
        ], $overrides));
    }

    private function attachEvidence(Experiment $experiment, bool $withValidation): void
    {
        AcquisitionFile::create([
            'experiment_id' => $experiment->id,
            'original_name' => $experiment->experiment_code . '.pcapng',
            'stored_name' => 'tests/' . $experiment->experiment_code . '.pcapng',
            'extension' => 'pcapng',
            'size_bytes' => 100,
        ]);

        if ($withValidation) {
            ValidationFile::create([
                'experiment_id' => $experiment->id,
                'original_name' => $experiment->experiment_code . '.log',
                'stored_name' => 'tests/' . $experiment->experiment_code . '.log',
                'extension' => 'log',
                'size_bytes' => 100,
            ]);
        }

        ExtractedFeature::create([
            'experiment_id' => $experiment->id,
            'final_attack_score' => $experiment->experiment_status === 'normal' ? 12 : 88,
            'attack_category' => $experiment->experiment_status === 'normal' ? 'Normal' : 'Strong Slowloris Indication',
            'raw_features' => ['tool_profile' => $experiment->tool_profile],
        ]);
    }
}
