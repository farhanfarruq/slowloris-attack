<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use League\Csv\Reader;

/**
 * Parser file validasi Snort.
 *
 * Mendukung:
 *  - alert.json / unified-json line-by-line
 *  - alert.csv  (header bebas, kolom umum: timestamp, classification, priority, src_ip, dst_ip, dst_port, msg)
 *  - alert.fast / .txt / .log standar Snort
 */
class ValidationParser
{
    public function parse(string $absolutePath, string $extension): array
    {
        $extension = strtolower($extension);
        $alerts = match ($extension) {
            'json'        => $this->parseJson($absolutePath),
            'csv'         => $this->parseCsv($absolutePath),
            'log', 'txt'  => $this->parseFast($absolutePath),
            default       => [],
        };

        return $this->summarize($alerts);
    }

    private function parseJson(string $path): array
    {
        $contents = file_get_contents($path);

        // Coba JSON Lines dulu
        $alerts = [];
        $lines = preg_split('/\R/', trim($contents));
        $allLinesValid = true;
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $obj = json_decode($line, true);
            if (!is_array($obj)) {
                $allLinesValid = false;
                break;
            }
            $alerts[] = $this->normalizeJsonAlert($obj);
        }

        if ($allLinesValid && $alerts) {
            return $alerts;
        }

        // Fallback: array JSON tunggal
        $data = json_decode($contents, true);
        if (is_array($data)) {
            if (array_is_list($data)) {
                return array_map(fn ($a) => $this->normalizeJsonAlert($a), $data);
            }
            if (isset($data['alerts']) && is_array($data['alerts'])) {
                return array_map(fn ($a) => $this->normalizeJsonAlert($a), $data['alerts']);
            }
        }

        return [];
    }

    private function parseCsv(string $path): array
    {
        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0);
            $rows = iterator_to_array($csv->getRecords());
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(function ($row) {
            $row = array_change_key_case($row, CASE_LOWER);
            return $this->normalizeJsonAlert([
                'timestamp'      => $row['timestamp'] ?? $row['time'] ?? null,
                'classification' => $row['classification'] ?? $row['type'] ?? null,
                'severity'       => $row['severity'] ?? $row['priority'] ?? null,
                'src_ip'         => $row['src_ip'] ?? $row['source_ip'] ?? null,
                'dst_ip'         => $row['dst_ip'] ?? $row['destination_ip'] ?? null,
                'src_port'       => $row['src_port'] ?? null,
                'dst_port'       => $row['dst_port'] ?? $row['destination_port'] ?? null,
                'protocol'       => $row['protocol'] ?? null,
                'msg'            => $row['msg'] ?? $row['message'] ?? null,
            ]);
        }, $rows);
    }

    private function parseFast(string $path): array
    {
        $contents = file_get_contents($path);
        $lines = preg_split('/\R/', $contents);

        $alerts = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Format umum: 11/05-10:05:11.123  [**] [1:1000:1] Possible Slow HTTP DoS [**] [Classification: ...] [Priority: 2] {TCP} 192.168.1.5:54321 -> 192.168.1.10:80
            if (preg_match('#^(\S+)\s+\[\*\*\]\s+\[\d+:\d+:\d+\]\s+(.+?)\s+\[\*\*\].*?Priority:\s*(\d+).*?\{(\w+)\}\s+([\d\.]+):(\d+)\s+->\s+([\d\.]+):(\d+)#', $line, $m)) {
                $alerts[] = $this->normalizeJsonAlert([
                    'timestamp'      => $m[1],
                    'msg'            => $m[2],
                    'severity'       => $this->priorityToSeverity((int) $m[3]),
                    'protocol'       => $m[4],
                    'src_ip'         => $m[5],
                    'src_port'       => (int) $m[6],
                    'dst_ip'         => $m[7],
                    'dst_port'       => (int) $m[8],
                    'classification' => 'snort-fast',
                ]);
            }
        }

        return $alerts;
    }

    private function priorityToSeverity(int $p): string
    {
        return match (true) {
            $p === 1 => 'high',
            $p === 2 => 'medium',
            default  => 'low',
        };
    }

    private function normalizeJsonAlert(array $obj): array
    {
        $severity = $obj['severity'] ?? null;
        if (is_numeric($severity)) {
            $severity = $this->priorityToSeverity((int) $severity);
        }

        $timestamp = null;
        if (!empty($obj['timestamp'])) {
            try {
                $timestamp = Carbon::parse($obj['timestamp']);
            } catch (\Throwable $e) {
                $timestamp = null;
            }
        }

        return [
            'timestamp'      => $timestamp,
            'msg'            => $obj['msg'] ?? $obj['message'] ?? null,
            'severity'       => $severity ?? 'low',
            'classification' => $obj['classification'] ?? $obj['alert_type'] ?? null,
            'protocol'       => $obj['protocol'] ?? null,
            'src_ip'         => $obj['src_ip'] ?? null,
            'src_port'       => $obj['src_port'] ?? null,
            'dst_ip'         => $obj['dst_ip'] ?? null,
            'dst_port'       => $obj['dst_port'] ?? null,
            'raw'            => $obj,
        ];
    }

    private function summarize(array $alerts): array
    {
        $total = count($alerts);

        $msgCount = [];
        $sevCount = ['high' => 0, 'medium' => 0, 'low' => 0];
        $srcCount = [];
        $dstPortCount = [];
        $timeline = [];

        foreach ($alerts as $a) {
            if (!empty($a['msg'])) {
                $msgCount[$a['msg']] = ($msgCount[$a['msg']] ?? 0) + 1;
            }
            $sev = strtolower($a['severity'] ?? 'low');
            if (!isset($sevCount[$sev])) $sevCount[$sev] = 0;
            $sevCount[$sev]++;

            if (!empty($a['src_ip'])) {
                $srcCount[$a['src_ip']] = ($srcCount[$a['src_ip']] ?? 0) + 1;
            }
            if (!empty($a['dst_port'])) {
                $key = (string) $a['dst_port'];
                $dstPortCount[$key] = ($dstPortCount[$key] ?? 0) + 1;
            }

            if ($a['timestamp'] instanceof Carbon) {
                $bucket = $a['timestamp']->format('Y-m-d H:i');
                $timeline[$bucket] = ($timeline[$bucket] ?? 0) + 1;
            }
        }

        arsort($msgCount);
        arsort($srcCount);
        arsort($dstPortCount);
        ksort($timeline);

        $highestSeverity = $sevCount['high'] > 0 ? 'high'
            : ($sevCount['medium'] > 0 ? 'medium' : 'low');

        $dominantAlert = $msgCount ? array_key_first($msgCount) : null;

        $matchesSlowHttp = false;
        if ($dominantAlert) {
            $needle = strtolower($dominantAlert);
            $matchesSlowHttp = str_contains($needle, 'slow')
                || str_contains($needle, 'slowloris')
                || str_contains($needle, 'http dos')
                || str_contains($needle, 'http denial');

            $httpAlertCount = ($dstPortCount['80'] ?? 0)
                + ($dstPortCount['8080'] ?? 0)
                + ($dstPortCount['443'] ?? 0);
            $httpAlertRatio = $httpAlertCount / max(1, $total);

            $matchesSlowHttp = $matchesSlowHttp
                || (
                    str_contains($needle, 'repeated tcp connection')
                    && $httpAlertRatio >= 0.8
                    && $total >= 30
                );
        }

        return [
            'alerts'                   => $alerts,
            'total_alerts'             => $total,
            'severity_count'           => $sevCount,
            'highest_severity'         => $highestSeverity,
            'dominant_alert_type'      => $dominantAlert,
            'top_source_ips'           => array_slice($srcCount, 0, 10, true),
            'top_destination_ports'    => array_slice($dstPortCount, 0, 10, true),
            'alert_timeline'           => $timeline,
            'matches_slow_http_pattern'=> $matchesSlowHttp,
            'parsed_summary'           => [
                'parser'   => 'snort',
                'unique_messages' => count($msgCount),
            ],
        ];
    }
}
