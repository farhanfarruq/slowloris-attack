<?php

namespace Tests\Unit;

use App\Services\AiValidationService;
use App\Services\AnalysisService;
use App\Services\ScoringService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AiValidationServiceTest extends TestCase
{
    public function test_detected_response_is_downgraded_when_evidence_contract_blocks_detection(): void
    {
        $service = $this->service();
        $method = $this->privateMethod('normalizeResponse');

        $result = $method->invoke($service, [
            'model_name' => 'llama-3.3-70b-versatile',
            'classification' => 'Slowloris Detected',
            'confidence_score' => 94,
            'reason' => 'Traffic appears malicious.',
            'supporting_indicators' => ['total_connections=800'],
            'missing_evidence' => [],
            'recommendation' => 'Investigate.',
        ], 'Groq Llama', [
            'evidence_contract' => [
                'slowloris_detected_allowed' => false,
                'gate_reasons' => ['HTTP burst pendek tidak boleh dilabeli Slowloris.'],
            ],
        ]);

        $this->assertSame('Inconclusive', $result['classification']);
        $this->assertLessThanOrEqual(40, $result['confidence_score']);
        $this->assertContains('HTTP burst pendek tidak boleh dilabeli Slowloris.', $result['missing_evidence']);
        $this->assertStringContainsString('Evidence contract menolak', $result['reason']);
    }

    public function test_detected_response_is_kept_when_evidence_contract_allows_detection(): void
    {
        $service = $this->service();
        $method = $this->privateMethod('normalizeResponse');

        $result = $method->invoke($service, [
            'model_name' => 'gpt-4o-mini',
            'classification' => 'Slowloris Detected',
            'confidence_score' => 88.555,
            'reason' => 'Long-lived HTTP, low bandwidth, and Snort evidence align.',
            'supporting_indicators' => [
                'long_lived_connections=200',
                'throughput_kbps=5',
                'dominant_alert_type=Slow HTTP',
            ],
            'missing_evidence' => [],
            'recommendation' => 'Confirm with packet timeline.',
        ], 'OpenAI', [
            'evidence_contract' => [
                'slowloris_detected_allowed' => true,
                'gate_reasons' => [],
            ],
        ]);

        $this->assertSame('Slowloris Detected', $result['classification']);
        $this->assertSame(88.56, $result['confidence_score']);
        $this->assertCount(3, $result['supporting_indicators']);
    }

    public function test_prompt_forces_json_and_evidence_contract_decision_boundary(): void
    {
        $service = $this->service();
        $method = $this->privateMethod('buildPrompt');

        $prompt = $method->invoke($service, [
            'evidence_contract' => [
                'slowloris_detected_allowed' => false,
            ],
        ]);

        $this->assertStringContainsString('Jawab HANYA JSON valid', $prompt['system']);
        $this->assertStringContainsString('payload.evidence_contract.slowloris_detected_allowed', $prompt['system']);
        $this->assertStringContainsString('Koneksi banyak saja', $prompt['system']);
        $this->assertStringContainsString('"evidence_contract"', $prompt['user']);
    }

    private function service(): AiValidationService
    {
        return new AiValidationService(new AnalysisService(new ScoringService()));
    }

    private function privateMethod(string $name): ReflectionMethod
    {
        $method = new ReflectionMethod(AiValidationService::class, $name);
        $method->setAccessible(true);

        return $method;
    }
}
