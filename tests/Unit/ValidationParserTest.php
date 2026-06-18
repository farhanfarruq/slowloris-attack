<?php

namespace Tests\Unit;

use App\Services\ValidationParser;
use PHPUnit\Framework\TestCase;

class ValidationParserTest extends TestCase
{
    public function test_large_fast_log_is_summarized_without_returning_every_alert(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'snort-fast-');
        $line = '06/17-07:41:20.027988  [**] [1:1000001:1] LOCAL LAB repeated TCP connection attempts [**] [Classification: Potentially Bad Traffic] [Priority: 3] {TCP} 192.168.56.102:47152 -> 192.168.56.103:80';

        file_put_contents($path, implode(PHP_EOL, array_fill(0, 1500, $line)));

        try {
            $summary = (new ValidationParser())->parse($path, 'log');
        } finally {
            @unlink($path);
        }

        $this->assertSame(1500, $summary['total_alerts']);
        $this->assertCount(1000, $summary['alerts']);
        $this->assertSame('LOCAL LAB repeated TCP connection attempts', $summary['dominant_alert_type']);
        $this->assertSame(['80' => 1500], $summary['top_destination_ports']);
    }
}
