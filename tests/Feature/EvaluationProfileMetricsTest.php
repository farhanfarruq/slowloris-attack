<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\ExtractedFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
