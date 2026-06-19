<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

/**
 * Parser sederhana untuk file hasil akuisisi dari Wireshark/dumpcap.
 *
 * - .csv  : ringkasan packet dengan kolom umum (No., Time, Source, Destination, Protocol, Length, Info)
 * - .json : ringkasan custom; raw JSON packet export besar ditolak agar tidak menghabiskan RAM
 * - .pcap / .pcapng : memakai tshark secara streaming jika tersedia; kalau tidak, fallback ke metadata file
 *
 * Tujuan: menghasilkan ringkasan numerik yang aman, bukan parsing forensik.
 */
class AcquisitionParser
{
    private const MAX_JSON_SUMMARY_BYTES = 10 * 1024 * 1024;

    public function parse(string $absolutePath, string $extension): array
    {
        $extension = strtolower(pathinfo($extension, PATHINFO_EXTENSION) ?: $extension);

        return match ($extension) {
            'csv'             => $this->parseCsv($absolutePath),
            'json'            => $this->parseJson($absolutePath),
            'pcap', 'pcapng'  => $this->parsePcap($absolutePath),
            default           => $this->emptySummary('Unsupported extension: ' . $extension),
        };
    }

    private function parseCsv(string $path): array
    {
        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();
            $columns = $csv->getHeader();
        } catch (\Throwable $e) {
            Log::warning('CSV parse failed: ' . $e->getMessage());
            return $this->emptySummary('CSV tidak dapat dibaca');
        }

        $total = 0;
        $sources = [];
        $destinations = [];
        $protocols = [];
        $connections = [];
        $lengthSum = 0;
        $lengthCount = 0;
        $tcp = 0;
        $udp = 0;
        $icmp = 0;
        $http = 0;

        try {
            foreach ($records as $row) {
                $row = array_change_key_case((array) $row, CASE_LOWER);
                $src = $row['source'] ?? $row['src'] ?? null;
                $dst = $row['destination'] ?? $row['dst'] ?? null;
                $proto = strtoupper((string) ($row['protocol'] ?? ''));
                $len = (int) ($row['length'] ?? 0);

                $total++;

                if ($src) {
                    $sources[$src] = ($sources[$src] ?? 0) + 1;
                }
                if ($dst) {
                    $destinations[$dst] = ($destinations[$dst] ?? 0) + 1;
                }
                if ($src && $dst) {
                    $connections[$src . '>' . $dst] = true;
                }
                if ($proto) {
                    $protocols[$proto] = ($protocols[$proto] ?? 0) + 1;
                }
                if ($len > 0) {
                    $lengthSum += $len;
                    $lengthCount++;
                }

                if (str_contains($proto, 'TCP')) {
                    $tcp++;
                }
                if (str_contains($proto, 'UDP')) {
                    $udp++;
                }
                if (str_contains($proto, 'ICMP')) {
                    $icmp++;
                }
                if (str_contains($proto, 'HTTP')) {
                    $http++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('CSV stream parse failed: ' . $e->getMessage());
            return $this->emptySummary('CSV tidak dapat dibaca');
        }

        arsort($sources);
        arsort($destinations);
        arsort($protocols);

        return [
            'total_packets'        => $total,
            'tcp_packets'          => $tcp,
            'http_packets'         => $http,
            'avg_packet_size'      => $lengthCount ? round($lengthSum / $lengthCount, 2) : null,
            'top_source_ips'       => array_slice($sources, 0, 10, true),
            'top_destination_ips'  => array_slice($destinations, 0, 10, true),
            'protocol_distribution'=> $protocols,
            'total_connections'    => $connections ? count($connections) : null,
            'avg_connection_duration' => null,
            'half_open_connections'=> null,
            'parsed_summary'       => [
                'parser'  => 'csv-stream',
                'columns' => $columns,
                'udp_packets' => $udp,
                'icmp_packets' => $icmp,
            ],
        ];
    }

    private function parseJson(string $path): array
    {
        $size = filesize($path) ?: 0;

        if ($size > self::MAX_JSON_SUMMARY_BYTES) {
            return $this->emptySummary(
                'JSON terlalu besar untuk diparse langsung. Upload ringkasan JSON, bukan raw packet export.'
            );
        }

        $contents = file_get_contents($path);
        $data = json_decode((string) $contents, true);

        if (!is_array($data)) {
            return $this->emptySummary('JSON tidak valid');
        }

        // Jika user upload ringkasan langsung (sesuai contoh JSON di dokumentasi).
        if (isset($data['packet_summary']) || isset($data['connection_summary'])) {
            $ps = $data['packet_summary'] ?? [];
            $cs = $data['connection_summary'] ?? [];

            return [
                'total_packets'        => $ps['total_packets'] ?? null,
                'tcp_packets'          => $ps['tcp_packets'] ?? null,
                'http_packets'         => $ps['http_packets'] ?? null,
                'avg_packet_size'      => $ps['avg_packet_size'] ?? null,
                'top_source_ips'       => $data['top_source_ips'] ?? [],
                'top_destination_ips'  => $data['top_destination_ips'] ?? [],
                'protocol_distribution'=> $data['protocol_distribution'] ?? [],
                'total_connections'    => $cs['total_connections'] ?? null,
                'avg_connection_duration' => $cs['avg_connection_duration_seconds'] ?? null,
                'half_open_connections'=> $cs['half_open_connections'] ?? null,
                'parsed_summary'       => [
                    'parser'   => 'json-summary',
                    'duration' => $ps['duration_seconds'] ?? null,
                    'throughput_kbps' => $cs['throughput_kbps'] ?? null,
                    'long_lived_connections' => $cs['long_lived_connections'] ?? null,
                    'connections_to_http_port' => $cs['connections_to_http_port'] ?? null,
                    'udp_packets' => $ps['udp_packets'] ?? null,
                    'icmp_packets' => $ps['icmp_packets'] ?? null,
                ],
            ];
        }

        // Bila berbentuk array besar dari JSON packet export, gunakan estimasi.
        if (array_is_list($data)) {
            $total = count($data);
            return [
                'total_packets'        => $total,
                'tcp_packets'          => null,
                'http_packets'         => null,
                'avg_packet_size'      => null,
                'top_source_ips'       => [],
                'top_destination_ips'  => [],
                'protocol_distribution'=> [],
                'total_connections'    => null,
                'avg_connection_duration' => null,
                'half_open_connections'=> null,
                'parsed_summary'       => ['parser' => 'json-list', 'count' => $total],
            ];
        }

        return $this->emptySummary('Format JSON tidak dikenali');
    }

    private function parsePcap(string $path): array
    {
        $size = filesize($path) ?: 0;
        $maxAutoParseBytes = ((int) config('upload.pcap_auto_parse_max_mb', 100)) * 1024 * 1024;

        if ($maxAutoParseBytes > 0 && $size > $maxAutoParseBytes) {
            return [
                'total_packets'        => null,
                'tcp_packets'          => null,
                'http_packets'         => null,
                'avg_packet_size'      => null,
                'top_source_ips'       => [],
                'top_destination_ips'  => [],
                'protocol_distribution'=> [],
                'total_connections'    => null,
                'avg_connection_duration' => null,
                'half_open_connections'=> null,
                'parsed_summary'       => [
                    'parser' => 'pcap-large-skip',
                    'note'   => 'PCAP/PCAPNG besar disimpan tanpa auto parse agar tidak menghabiskan RAM. Upload ringkasan CSV/JSON untuk analisis.',
                    'size_bytes' => $size,
                    'auto_parse_limit_bytes' => $maxAutoParseBytes,
                ],
            ];
        }

        $tshark = $this->findExecutable('tshark');

        if ($tshark) {
            $summary = $this->parsePcapWithTshark($tshark, $path);

            if ($summary !== null) {
                return $summary;
            }
        }

        return [
            'total_packets'        => null,
            'tcp_packets'          => null,
            'http_packets'         => null,
            'avg_packet_size'      => null,
            'top_source_ips'       => [],
            'top_destination_ips'  => [],
            'protocol_distribution'=> [],
            'total_connections'    => null,
            'avg_connection_duration' => null,
            'half_open_connections'=> null,
            'parsed_summary'       => [
                'parser' => 'fallback',
                'note'   => 'Helper parser PCAP tidak tersedia atau gagal. Upload juga ringkasan JSON/CSV agar parsing lengkap.',
                'size_bytes' => filesize($path) ?: null,
            ],
        ];
    }

    private function parsePcapWithTshark(string $tshark, string $path): ?array
    {
        $command = [
            $tshark,
            '-r', $path,
            '-T', 'fields',
            '-E', "separator=\t",
            '-E', 'occurrence=f',
            '-e', 'frame.len',
            '-e', 'frame.time_epoch',
            '-e', 'frame.protocols',
            '-e', 'ip.src',
            '-e', 'ip.dst',
            '-e', 'tcp.stream',
            '-e', 'tcp.srcport',
            '-e', 'tcp.dstport',
            '-e', 'tcp.flags.fin',
            '-e', 'tcp.flags.reset',
        ];

        $process = @proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);

        $total = 0;
        $tcp = 0;
        $http = 0;
        $lengthSum = 0;
        $lengthCount = 0;
        $sources = [];
        $destinations = [];
        $protocols = [];
        $streams = [];

        try {
            while (($line = fgets($pipes[1])) !== false) {
                $line = rtrim($line, "\r\n");

                if ($line === '') {
                    continue;
                }

                [$len, $timeEpoch, $protoStack, $src, $dst, $stream, $srcPort, $dstPort, $tcpFin, $tcpReset] =
                    array_pad(explode("\t", $line), 10, '');

                $total++;
                $length = (int) $len;
                if ($length > 0) {
                    $lengthSum += $length;
                    $lengthCount++;
                }

                $protoStack = strtolower($protoStack);
                if (str_contains($protoStack, 'tcp')) {
                    $tcp++;
                    $protocols['TCP'] = ($protocols['TCP'] ?? 0) + 1;
                }

                if (str_contains($protoStack, 'http') || $srcPort === '80' || $dstPort === '80') {
                    $http++;
                    $protocols['HTTP'] = ($protocols['HTTP'] ?? 0) + 1;
                }

                if ($src !== '') {
                    $sources[$src] = ($sources[$src] ?? 0) + 1;
                }
                if ($dst !== '') {
                    $destinations[$dst] = ($destinations[$dst] ?? 0) + 1;
                }
                if ($stream !== '') {
                    if (!isset($streams[$stream])) {
                        $streams[$stream] = [
                            'first' => null,
                            'last' => null,
                            'http_port' => false,
                            'closed' => false,
                        ];
                    }

                    $timestamp = is_numeric($timeEpoch) ? (float) $timeEpoch : null;
                    if ($timestamp !== null) {
                        $streams[$stream]['first'] = $streams[$stream]['first'] === null
                            ? $timestamp
                            : min($streams[$stream]['first'], $timestamp);
                        $streams[$stream]['last'] = $streams[$stream]['last'] === null
                            ? $timestamp
                            : max($streams[$stream]['last'], $timestamp);
                    }

                    if ($srcPort === '80' || $dstPort === '80') {
                        $streams[$stream]['http_port'] = true;
                    }

                    if ($tcpFin === '1' || $tcpReset === '1') {
                        $streams[$stream]['closed'] = true;
                    }
                }
            }
        } finally {
            fclose($pipes[1]);
            $exitCode = proc_close($process);
        }

        if ($total === 0) {
            return null;
        }

        $parserWarning = ($exitCode ?? 0) !== 0
            ? 'tshark exit code ' . $exitCode . '; summary dibuat dari packet yang masih dapat dibaca.'
            : null;

        arsort($sources);
        arsort($destinations);
        arsort($protocols);

        $streamDurations = [];
        $longLivedConnections = 0;
        $connectionsToHttpPort = 0;
        $openHttpConnections = 0;

        foreach ($streams as $stream) {
            if ($stream['http_port']) {
                $connectionsToHttpPort++;

                if (!$stream['closed']) {
                    $openHttpConnections++;
                }
            }

            if ($stream['first'] !== null && $stream['last'] !== null) {
                $duration = max(0, $stream['last'] - $stream['first']);
                $streamDurations[] = $duration;

                if ($duration >= 30) {
                    $longLivedConnections++;
                }
            }
        }

        $durationSeconds = $streamDurations ? max($streamDurations) : null;
        $avgConnectionDuration = $streamDurations
            ? round(array_sum($streamDurations) / count($streamDurations), 2)
            : null;
        $throughputKbps = ($durationSeconds && $durationSeconds > 0 && $lengthSum > 0)
            ? round(($lengthSum * 8) / 1000 / $durationSeconds, 2)
            : null;

        return [
            'total_packets'        => $total,
            'tcp_packets'          => $tcp,
            'http_packets'         => $http,
            'avg_packet_size'      => $lengthCount ? round($lengthSum / $lengthCount, 2) : null,
            'top_source_ips'       => array_slice($sources, 0, 10, true),
            'top_destination_ips'  => array_slice($destinations, 0, 10, true),
            'protocol_distribution'=> $protocols,
            'total_connections'    => count($streams) ?: null,
            'avg_connection_duration' => $avgConnectionDuration,
            'half_open_connections'=> $openHttpConnections ?: null,
            'parsed_summary'       => [
                'parser' => 'tshark-fields-stream',
                'note'   => 'HTTP dihitung dari decoded HTTP atau TCP port 80.',
                'duration' => $durationSeconds,
                'throughput_kbps' => $throughputKbps,
                'long_lived_connections' => $longLivedConnections,
                'connections_to_http_port' => $connectionsToHttpPort,
                'open_http_connections' => $openHttpConnections,
                'warning' => $parserWarning,
            ],
        ];
    }

    private function findExecutable(string $binary): ?string
    {
        $output = [];
        $code = 1;
        @exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null', $output, $code);

        if ($code !== 0 || empty($output[0])) {
            return null;
        }

        return trim($output[0]);
    }

    private function emptySummary(string $note): array
    {
        return [
            'total_packets'        => null,
            'tcp_packets'          => null,
            'http_packets'         => null,
            'avg_packet_size'      => null,
            'top_source_ips'       => [],
            'top_destination_ips'  => [],
            'protocol_distribution'=> [],
            'total_connections'    => null,
            'avg_connection_duration' => null,
            'half_open_connections'=> null,
            'parsed_summary'       => ['note' => $note],
        ];
    }
}
