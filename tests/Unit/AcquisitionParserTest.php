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

    public function test_csv_summary_preserves_udp_and_icmp_packet_counts(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'acq-csv-');
        file_put_contents($path, implode("\n", [
            'No.,Time,Source,Destination,Protocol,Length,Info',
            '1,0.1,10.0.0.2,10.0.0.10,TCP,74,SYN',
            '2,0.2,10.0.0.2,10.0.0.10,UDP,90,Datagram',
            '3,0.3,10.0.0.2,10.0.0.10,ICMP,98,Echo request',
            '4,0.4,10.0.0.2,10.0.0.10,HTTP,120,GET /',
        ]));

        try {
            $summary = (new AcquisitionParser())->parse($path, 'csv');
        } finally {
            @unlink($path);
        }

        $this->assertSame(4, $summary['total_packets']);
        $this->assertSame(1, $summary['tcp_packets']);
        $this->assertSame(1, $summary['http_packets']);
        $this->assertSame(1, $summary['parsed_summary']['udp_packets']);
        $this->assertSame(1, $summary['parsed_summary']['icmp_packets']);
    }

    public function test_json_summary_preserves_transport_fields(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'acq-json-');
        file_put_contents($path, json_encode([
            'packet_summary' => [
                'total_packets' => 1200,
                'tcp_packets' => 100,
                'udp_packets' => 900,
                'icmp_packets' => 200,
                'http_packets' => 0,
                'duration_seconds' => 30,
            ],
            'connection_summary' => [
                'total_connections' => 300,
                'throughput_kbps' => 12000,
                'connections_to_http_port' => 0,
            ],
            'protocol_distribution' => [
                'UDP' => 900,
                'ICMP' => 200,
            ],
        ]));

        try {
            $summary = (new AcquisitionParser())->parse($path, 'json');
        } finally {
            @unlink($path);
        }

        $this->assertSame(1200, $summary['total_packets']);
        $this->assertSame(0, $summary['http_packets']);
        $this->assertSame(900, $summary['parsed_summary']['udp_packets']);
        $this->assertSame(200, $summary['parsed_summary']['icmp_packets']);
        $this->assertSame(12000, $summary['parsed_summary']['throughput_kbps']);
        $this->assertSame(0, $summary['parsed_summary']['connections_to_http_port']);
    }
}
