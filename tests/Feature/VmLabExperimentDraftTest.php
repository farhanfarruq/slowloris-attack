<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VmLabExperimentDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_empty_vm_drafts_for_all_tool_profiles_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('experiments.vm-drafts'))
            ->assertRedirect(route('experiments.index', ['target_platform' => 'vm_ubuntu_server']));

        $this->assertSame(6, Experiment::count());
        $this->assertEqualsCanonicalizing(
            ['slowloris', 'loic', 'hoic', 'hping3', 'torshammer', 'xerxes'],
            Experiment::pluck('tool_profile')->all(),
        );

        Experiment::query()->each(function (Experiment $experiment): void {
            $this->assertSame('vm_ubuntu_server', $experiment->target_platform);
            $this->assertSame('created', $experiment->status);
            $this->assertSame('pending', $experiment->experiment_status);
            $this->assertSame('unknown', $experiment->traffic_type);
            $this->assertNull($experiment->target_ip);
            $this->assertNull($experiment->source_ip);
            $this->assertNull($experiment->capture_duration);
            $this->assertStringContainsString('tidak membuat command', $experiment->notes);
        });

        $this->actingAs($admin)->post(route('experiments.vm-drafts'));
        $this->assertSame(6, Experiment::count(), 'Draft VM tidak boleh diduplikasi.');
    }
}
