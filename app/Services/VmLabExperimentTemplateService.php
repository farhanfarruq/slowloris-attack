<?php

namespace App\Services;

use App\Models\Experiment;
use Illuminate\Support\Carbon;

class VmLabExperimentTemplateService
{
    public function __construct(private ToolProfileService $toolProfiles)
    {
    }

    public function createMissingDrafts(?int $userId): array
    {
        $created = [];
        $existing = [];

        foreach ($this->toolProfiles->options() as $profile) {
            $key = $profile['key'];
            $draft = Experiment::query()
                ->where('tool_profile', $key)
                ->where('target_platform', 'vm_ubuntu_server')
                ->where('analysis_profile_key', $key)
                ->where('status', 'created')
                ->where('scenario_key', 'vm-' . $key . '-pending')
                ->first();

            if ($draft) {
                $existing[] = $draft;
                continue;
            }

            $experiment = Experiment::create([
                'experiment_code' => $this->nextExperimentCode(),
                'name' => 'VM Ubuntu Target - ' . $profile['label'] . ' - Draft Capture',
                'experiment_date' => Carbon::today(),
                'network_interface' => null,
                'target_ip' => null,
                'source_ip' => null,
                'capture_duration' => null,
                'notes' => $this->draftNotes($profile['label']),
                'scenario_key' => 'vm-' . $key . '-pending',
                'traffic_type' => 'unknown',
                'status' => 'created',
                'experiment_status' => 'pending',
                'ground_truth_label' => null,
                'tool_profile' => $key,
                'attack_pattern' => $profile['default_attack_pattern'] ?? null,
                'analysis_profile_key' => $key,
                'target_platform' => 'vm_ubuntu_server',
                'user_id' => $userId,
            ]);

            $created[] = $experiment;
        }

        return [
            'created' => $created,
            'existing' => $existing,
        ];
    }

    private function nextExperimentCode(): string
    {
        $next = ((int) Experiment::max('id')) + 1;

        do {
            $code = 'EXP-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $next++;
        } while (Experiment::where('experiment_code', $code)->exists());

        return $code;
    }

    private function draftNotes(string $label): string
    {
        return implode("\n", [
            'Draft eksperimen defensif untuk capture nyata pada VM target Ubuntu Server.',
            'Tool profile: ' . $label . '.',
            'Aplikasi tidak membuat command, script, payload, automation serangan, scanner publik, atau data sintetis AI.',
            'Isi IP target, IP sumber, interface, durasi capture, lalu upload PCAP/Wireshark dan log Snort dari eksperimen lab nyata.',
            'Gunakan metadata ESP32/drone hanya jika dataset akuisisi benar-benar berasal dari objek tersebut.',
        ]);
    }
}
