<?php

namespace Tests\Unit;

use App\Services\AcquisitionParser;
use Tests\TestCase;

class AcquisitionParserTest extends TestCase
{
    public function test_large_pcap_is_saved_without_auto_parse(): void
    {
        config(['upload.pcap_auto_parse_max_mb' => 1]);

        $path = tempnam(sys_get_temp_dir(), 'large-pcap-');
        file_put_contents($path, str_repeat('x', (1024 * 1024) + 1));

        try {
            $summary = (new AcquisitionParser())->parse($path, 'pcapng');
        } finally {
            @unlink($path);
        }

        $this->assertSame('pcap-large-skip', $summary['parsed_summary']['parser']);
        $this->assertSame((1024 * 1024) + 1, $summary['parsed_summary']['size_bytes']);
        $this->assertNull($summary['total_packets']);
    }
}
