<?php

namespace Tests\Unit;

use App\Services\AiPromptBuilder;
use PHPUnit\Framework\TestCase;

class AiPromptBuilderTest extends TestCase
{
    public function test_prompt_is_tool_profile_specific(): void
    {
        $prompt = (new AiPromptBuilder())->build([
            'tool_profile' => 'loic',
            'attack_pattern' => 'http_flood',
            'evidence_contract' => ['detected_allowed' => true],
        ]);

        $this->assertStringContainsString('payload.tool_profile: loic', $prompt['system']);
        $this->assertStringContainsString('LOIC Detected', $prompt['system']);
        $this->assertStringContainsString('Jangan memakai ulang indikator dari tool profile lain', $prompt['system']);
        $this->assertStringContainsString('seluruh teks naratif di dalam JSON harus berbahasa Indonesia', $prompt['user']);
        $this->assertStringContainsString('Analisis perilaku flood berupa volume paket atau request yang tinggi', $prompt['system']);
        $this->assertStringNotContainsString('Do not reuse indicators from another tool profile', $prompt['system']);
        $this->assertStringNotContainsString('Slowloris Detected, Inconclusive', $prompt['system']);
    }
}
