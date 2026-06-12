<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

/**
 * Parser sederhana untuk file hasil akuisisi dari Wireshark/dumpcap.
 *
 * - .csv  : ringkasan packet dengan kolom umum (No., Time, Source, Destination, Protocol, Length, Info)
 * - .json : ekspor JSON atau ringkasan custom
 * - .pcap / .pcapng : memakai helper CLI Wireshark jika tersedia; kalau tidak, fallback ke metadata file.
 *
 * Tujuan: menghasilkan ringkasan numerik yang aman, bukan parsing forensik.
 */
class AcquisitionParser
{
    public function parse(string $absolutePath, string $extension): array
    {
        $extension = strtolower($extension);

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
            $rows = iterator_to_array($csv->getRecords());
        } catch (\Throwable $e) {
            Log::warning('CSV parse failed: ' . $e->getMessage());
            return $this->emptySummary('CSV tidak dapat dibaca');
        }

        $total = count($rows);
        $sources = [];
        $destinations = [];
        $protocols = [];
        $lengths = [];
        $tcp = 0;
        $http = 0;

        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $src = $row['source'] ?? $row['src'] ?? null;
            $dst = $row['destination'] ?? $row['dst'] ?? null;
            $proto = strtoupper($row['protocol'] ?? '');
            $len = (int) ($row['length'] ?? 0);

            if ($src) $sources[$src] = ($sources[$src] ?? 0) + 1;
            if ($dst) $destinations[$dst] = ($destinations[$dst] ?? 0) + 1;
            if ($proto) $protocols[$proto] = ($protocols[$proto] ?? 0) + 1;
            if ($len > 0) $lengths[] = $len;

            if (str_contains($proto, 'TCP')) $tcp++;
            if (str_contains($proto, 'HTTP')) $http++;
        }

        arsort($sources);
        arsort($destinations);
        arsort($protocols);

        return [
            'total_packets'        => $total,
            'tcp_packets'          => $tcp,
            'http_packets'         => $http,
            'avg_packet_size'      => $lengths ? array_sum($lengths) / count($lengths) : null,
            'top_source_ips'       => array_slice($sources, 0, 10, true),
            'top_destination_ips'  => array_slice($destinations, 0, 10, true),
            'protocol_distribution'=> $protocols,
            'total_connections'    => $this->estimateConnections($rows),
            'avg_connection_duration' => null,
            'half_open_connections'=> null,
            'parsed_summary'       => [
                'parser'  => 'csv',
                'columns' => array_keys((array) ($rows[0] ?? [])),
            ],
        ];
    }

    private function parseJson(string $path): array
    {
        $contents = file_get_contents($path);
        $data = json_decode($contents, true);

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
        // Coba gunakan helper CLI Wireshark jika tersedia, jika tidak fallback ke estimasi ukuran.
        $tshark = trim((string) @shell_exec('command -v tshark 2>/dev/null'));

        if ($tshark) {
            $fieldsCmd = escapeshellcmd($tshark)
                . ' -r ' . escapeshellarg($path)
                . ' -T fields'
                . ' -E separator=' . escapeshellarg("\t")
                . ' -E occurrence=f'
                . ' -e frame.len'
                . ' -e frame.protocols'
                . ' -e ip.src'
                . ' -e ip.dst'
                . ' -e tcp.stream'
                . ' -e tcp.srcport'
                . ' -e tcp.dstport'
                . ' 2>/dev/null';

            $fieldsOutput = @shell_exec($fieldsCmd);

            if ($fieldsOutput) {
                $total = 0;
                $tcp = 0;
                $http = 0;
                $lengths = [];
                $sources = [];
                $destinations = [];
                $protocols = [];
                $streams = [];

                foreach (preg_split('/\R/', trim($fieldsOutput)) as $line) {
                    if ($line === '') {
                        continue;
                    }

                    [$len, $protoStack, $src, $dst, $stream, $srcPort, $dstPort] =
                        array_pad(explode("\t", $line), 7, '');

                    $total++;
                    $length = (int) $len;
                    if ($length > 0) {
                        $lengths[] = $length;
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
                        $streams[$stream] = true;
                    }
                }

                arsort($sources);
                arsort($destinations);
                arsort($protocols);

                return [
                    'total_packets'        => $total,
                    'tcp_packets'          => $tcp,
                    'http_packets'         => $http,
                    'avg_packet_size'      => $lengths ? round(array_sum($lengths) / count($lengths), 2) : null,
                    'top_source_ips'       => array_slice($sources, 0, 10, true),
                    'top_destination_ips'  => array_slice($destinations, 0, 10, true),
                    'protocol_distribution'=> $protocols,
                    'total_connections'    => count($streams) ?: null,
                    'avg_connection_duration' => null,
                    'half_open_connections'=> null,
                    'parsed_summary'       => [
                        'parser' => 'tshark-fields',
                        'note'   => 'HTTP dihitung dari decoded HTTP atau TCP port 80.',
                    ],
                ];
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
                'note'   => 'Helper parser PCAP tidak tersedia. Upload juga ringkasan JSON/CSV agar parsing lengkap.',
                'size_bytes' => filesize($path) ?: null,
            ],
        ];
    }

    private function estimateConnections(array $rows): ?int
    {
        $pairs = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $src = $row['source'] ?? null;
            $dst = $row['destination'] ?? null;
            if ($src && $dst) {
                $pairs[$src . '>' . $dst] = true;
            }
        }
        return $pairs ? count($pairs) : null;
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
            'parsed_summary'       => ['parser' => 'none', 'note' => $note],
        ];
    }
}
