<?php

namespace Tests\Unit;

use App\Services\ToolProfileService;
use PHPUnit\Framework\TestCase;

class ToolProfileServiceTest extends TestCase
{
    public function test_profiles_include_required_research_tools_and_safe_fallback(): void
    {
        $service = new ToolProfileService();

        $this->assertSame(
            ['slowloris', 'loic', 'hoic', 'hping3', 'torshammer', 'xerxes'],
            $service->keys(),
        );
        $this->assertSame('slowloris', $service->normalize('unknown-profile'));
        $this->assertSame('Hping3 Detected', $service->detectedLabel('hping3'));
    }
}
