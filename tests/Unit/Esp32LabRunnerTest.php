<?php

namespace Tests\Unit;

use App\Services\Esp32LabRunner;
use Tests\TestCase;

class Esp32LabRunnerTest extends TestCase
{
    public function test_subprocess_commands_are_argument_arrays_and_preserve_paths_with_spaces(): void
    {
        config([
            'esp32.dumpcap_binary' => '/usr/bin/dumpcap',
            'esp32.capture_interface' => 'wlp8s0',
            'esp32.capture_filter' => 'host 192.168.4.1 and tcp port 80',
            'esp32.snort_binary' => '/usr/local/bin/snort',
            'esp32.snort_config' => '/usr/local/etc/snort/snort.lua',
        ]);

        $runner = app(Esp32LabRunner::class);
        $pcap = '/tmp/VsCode Project/EXP-001.pcapng';
        $logs = '/tmp/VsCode Project/logs';

        $this->assertSame([
            '/usr/bin/dumpcap', '-i', 'wlp8s0', '-f',
            'host 192.168.4.1 and tcp port 80', '-w', $pcap,
        ], $runner->captureCommand($pcap));

        $this->assertSame([
            '/usr/local/bin/snort', '-q', '-c', '/usr/local/etc/snort/snort.lua',
            '-r', $pcap, '-A', 'alert_fast', '-l', $logs,
            '--lua', 'alert_fast = { file = true }',
        ], $runner->snortCommand($pcap, $logs));
    }
}
