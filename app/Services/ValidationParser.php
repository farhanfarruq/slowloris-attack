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
    private const MAX_RETURNED_ALERTS = 1000;

    public function parse(string $absolutePath, string $extension): array
    {
        $extension = strtolower(pathinfo($extension, PATHINFO_EXTENSION) ?: $extension);

        if (in_array($extension, ['log', 'txt'], true)) {
            return $this->parseFastSummary($absolutePath);
        }

        $alerts = match ($extension) {
            'json'        => $this->parseJson($absolutePath),
            'csv'         => $this->parseCsv($absolutePath),
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

            $alert = $this->parseFastLine($line);
            if ($alert !== null) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    private function parseFastSummary(string $path): array
    {
        $state = $this->emptySummaryState();
        $file = new \SplFileObject($path, 'r');

        while (!$file->eof()) {
            $line = trim((string) $file->fgets());
            if ($line === '') {
                continue;
            }

            $alert = $this->parseFastLine($line);
            if ($alert !== null) {
                $this->addAlertToSummary($state, $alert);
            }
        }

        return $this->finalizeSummaryState($state);
    }

    private function parseFastLine(string $line): ?array
    {
        // Format umum: 11/05-10:05:11.123  [**] [1:1000:1] Possible Slow HTTP DoS [**] [Classification: ...] [Priority: 2] {TCP} 192.168.1.5:54321 -> 192.168.1.10:80
        if (!preg_match('#^(\S+)\s+\[\*\*\]\s+\[\d+:\d+:\d+\]\s+(.+?)\s+\[\*\*\].*?Priority:\s*(\d+).*?\{(\w+)\}\s+([\d\.]+):(\d+)\s+->\s+([\d\.]+):(\d+)#', $line, $m)) {
            return null;
        }

        return $this->normalizeJsonAlert([
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
        $state = $this->emptySummaryState();

        foreach ($alerts as $alert) {
            $this->addAlertToSummary($state, $alert);
        }

        return $this->finalizeSummaryState($state);
    }

    private function emptySummaryState(): array
    {
        return [
            'alerts' => [],
            'total' => 0,
            'msg_count' => [],
            'severity_count' => ['high' => 0, 'medium' => 0, 'low' => 0],
            'source_count' => [],
            'destination_port_count' => [],
            'timeline' => [],
        ];
    }

    private function addAlertToSummary(array &$state, array $alert): void
    {
        $state['total']++;

        if (count($state['alerts']) < self::MAX_RETURNED_ALERTS) {
            $state['alerts'][] = $alert;
        }

        if (!empty($alert['msg'])) {
            $state['msg_count'][$alert['msg']] = ($state['msg_count'][$alert['msg']] ?? 0) + 1;
        }

        $sev = strtolower($alert['severity'] ?? 'low');
        if (!isset($state['severity_count'][$sev])) {
            $state['severity_count'][$sev] = 0;
        }
        $state['severity_count'][$sev]++;

        if (!empty($alert['src_ip'])) {
            $state['source_count'][$alert['src_ip']] = ($state['source_count'][$alert['src_ip']] ?? 0) + 1;
        }

        if (!empty($alert['dst_port'])) {
            $key = (string) $alert['dst_port'];
            $state['destination_port_count'][$key] = ($state['destination_port_count'][$key] ?? 0) + 1;
        }

        if (($alert['timestamp'] ?? null) instanceof Carbon) {
            $bucket = $alert['timestamp']->format('Y-m-d H:i');
            $state['timeline'][$bucket] = ($state['timeline'][$bucket] ?? 0) + 1;
        }
    }

    private function finalizeSummaryState(array $state): array
    {
        $alerts = $state['alerts'];
        $total = $state['total'];
        $msgCount = $state['msg_count'];
        $sevCount = $state['severity_count'];
        $srcCount = $state['source_count'];
        $dstPortCount = $state['destination_port_count'];
        $timeline = $state['timeline'];

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
                'severity_count' => $sevCount,
            ],
        ];
    }
}
