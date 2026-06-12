<?php

namespace Tests\Feature;

use App\Models\AiResult;
use App\Models\Experiment;
use App\Models\User;
use App\Services\AiValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiValidationVotingTest extends TestCase
{
    use RefreshDatabase;

    public function test_vote_uses_latest_validation_run_only(): void
    {
        $experiment = Experiment::create([
            'experiment_code' => 'EXP-AI-VOTE-001',
            'name' => 'AI voting regression',
            'experiment_date' => now(),
            'network_interface' => 'eth0',
            'target_ip' => '192.168.56.10',
            'source_ip' => '192.168.56.5',
            'capture_duration' => 600,
            'scenario_key' => 'slow-http',
            'traffic_type' => 'slowloris_lab',
            'status' => 'ai_validated',
            'experiment_status' => 'suspicious',
            'ground_truth_label' => 'slowloris_lab',
            'user_id' => User::factory()->create()->id,
        ]);

        foreach (range(1, 8) as $index) {
            $this->createAiResult($experiment, 'old-run', 'old-model-' . $index, 'Inconclusive', 50, now()->subHour());
        }

        $this->createAiResult($experiment, 'latest-run', 'openai/gpt-4o-mini', 'Suspicious', 85, now());
        $this->createAiResult($experiment, 'latest-run', 'llama-3.3-70b-versatile', 'Suspicious', 60, now());

        $service = app(AiValidationService::class);
        $vote = $service->vote($experiment->fresh());

        $this->assertSame(['Suspicious' => 2], $vote['voting_summary']['tally']);
        $this->assertSame('Suspicious', $vote['voting_summary']['top_classification']);
        $this->assertSame(72.5, $vote['voting_average_confidence']);
        $this->assertCount(2, $service->latestResults($experiment->fresh()));
    }

    private function createAiResult(
        Experiment $experiment,
        string $runId,
        string $modelName,
        string $classification,
        float $confidence,
        mixed $createdAt
    ): AiResult {
        return AiResult::create([
            'experiment_id' => $experiment->id,
            'validation_run_id' => $runId,
            'model_name' => $modelName,
            'model_version' => $modelName,
            'classification' => $classification,
            'confidence_score' => $confidence,
            'reason' => 'fixture',
            'supporting_indicators' => [],
            'missing_evidence' => [],
            'recommendation' => 'fixture',
            'raw_request' => [],
            'raw_response' => [],
            'is_simulated' => false,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
