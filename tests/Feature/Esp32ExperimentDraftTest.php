<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Esp32ExperimentDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_esp32_drafts_without_relabeling_existing_vm_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $legacy = $this->experiment($admin, 'EXP-900', 'vm_ubuntu_server');

        $this->actingAs($admin)
            ->post(route('experiments.esp32-drafts'))
            ->assertRedirect(route('experiments.index', ['target_platform' => 'esp32']));

        $this->assertSame(7, Experiment::count());
        $this->assertSame('vm_ubuntu_server', $legacy->fresh()->target_platform);

        Experiment::where('target_platform', 'esp32')->each(function (Experiment $experiment): void {
            $this->assertSame('wlp8s0', $experiment->network_interface);
            $this->assertSame('192.168.4.1', $experiment->target_ip);
            $this->assertSame('created', $experiment->status);
            $this->assertStringContainsString('tidak membuat command serangan', $experiment->notes);
        });

        $this->actingAs($admin)->post(route('experiments.esp32-drafts'));
        $this->assertSame(7, Experiment::count(), 'Draft ESP32 tidak boleh diduplikasi.');
    }

    public function test_editing_historical_experiment_cannot_relabel_it_as_esp32(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $experiment = $this->experiment($admin, 'EXP-901', 'vm_ubuntu_server');

        $this->actingAs($admin)->put(route('experiments.update', $experiment), [
            'name' => $experiment->name,
            'experiment_date' => $experiment->experiment_date->toDateString(),
            'scenario_key' => $experiment->scenario_key ?: 'legacy',
            'tool_profile' => $experiment->tool_profile ?: 'slowloris',
            'target_platform' => 'esp32',
            'traffic_type' => $experiment->traffic_type,
        ])->assertSessionHasNoErrors();

        $this->assertSame('vm_ubuntu_server', $experiment->fresh()->target_platform);
    }

    public function test_lab_page_explains_host_and_esp32_boundary(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('lab.index'))
            ->assertOk()
            ->assertSee('ESP32 fisik sebagai target HTTP')
            ->assertSee('php artisan esp32:readiness');
    }

    private function experiment(User $user, string $code, string $target): Experiment
    {
        return Experiment::create([
            'experiment_code' => $code,
            'name' => 'Historical experiment',
            'experiment_date' => now()->toDateString(),
            'scenario_key' => 'legacy',
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
