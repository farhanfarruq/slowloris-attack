<?php

namespace App\Services;

use App\Models\Experiment;
use Illuminate\Support\Carbon;

class Esp32ExperimentTemplateService
{
    public function __construct(private ToolProfileService $toolProfiles) {}

    public function createMissingDrafts(?int $userId): array
    {
        $created = [];
        $existing = [];

        foreach ($this->toolProfiles->options() as $profile) {
            $key = $profile['key'];
            $draft = Experiment::query()
                ->where('tool_profile', $key)
                ->where('target_platform', 'esp32')
                ->where('analysis_profile_key', $key)
                ->where('status', 'created')
                ->where('scenario_key', 'esp32-'.$key.'-pending')
                ->first();

            if ($draft) {
                $existing[] = $draft;

                continue;
            }

            $experiment = Experiment::create([
                'experiment_code' => $this->nextExperimentCode(),
                'name' => 'ESP32 Target - '.$profile['label'].' - Draft Capture',
                'experiment_date' => Carbon::today(),
                'network_interface' => config('esp32.capture_interface'),
                'target_ip' => config('esp32.host'),
                'source_ip' => null,
                'capture_duration' => null,
                'notes' => $this->draftNotes($profile['label']),
                'scenario_key' => 'esp32-'.$key.'-pending',
                'traffic_type' => 'unknown',
                'status' => 'created',
                'experiment_status' => 'pending',
                'ground_truth_label' => null,
                'tool_profile' => $key,
                'attack_pattern' => $profile['default_attack_pattern'] ?? null,
                'analysis_profile_key' => $key,
                'target_platform' => 'esp32',
                'user_id' => $userId,
            ]);

            $created[] = $experiment;
        }

        return compact('created', 'existing');
    }

    private function nextExperimentCode(): string
    {
        $next = ((int) Experiment::max('id')) + 1;

        do {
            $code = 'EXP-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $next++;
        } while (Experiment::where('experiment_code', $code)->exists());

        return $code;
    }

    private function draftNotes(string $label): string
    {
        return implode("\n", [
            'Draft eksperimen defensif untuk capture nyata dengan ESP32 sebagai target HTTP fisik.',
            'Tool profile: '.$label.'.',
            'Ubuntu laptop menjalankan client lab, dumpcap, TShark, dan Snort; ESP32 hanya menjadi target.',
            'Aplikasi tidak membuat command serangan, scanner publik, payload, atau data sintetis AI.',
            'Jalankan readiness host sebelum capture dan isi ground truth hanya dari eksperimen nyata.',
        ]);
    }
}
