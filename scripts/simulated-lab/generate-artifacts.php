<?php

declare(strict_types=1);

/**
 * Generates offline-only packet evidence for dashboard validation.
 * It never opens a socket, sends a packet, starts a process, or contacts a host.
 *
 * Usage: php scripts/simulated-lab/generate-artifacts.php [output-directory]
 */
final class OfflineLabArtifactGenerator
{
    private const SOURCE_IP = '198.18.0.10';
    private const TARGET_IP = '198.18.0.20';
    private const START_EPOCH = 1787191200.0; // 2026-08-20T02:00:00Z

    /** @var array<int, array{time: float, frame: string}> */
    private array $packets = [];
    private int $ipId = 1;

    /** @return array<string, array{label: string, profile: string, attack_pattern: string, indicators: list<string>, alert_groups: list<array{count: int, message: string, priority: int, protocol: string, port: int}>}> */
    private function scenarios(): array
    {
        return [
            'slowloris' => [
                'label' => 'Slowloris / Slow HTTP Headers', 'profile' => 'slowloris', 'attack_pattern' => 'slow_http',
                'expected_decision' => 'attack_detected',
                'indicators' => ['36 koneksi HTTP tetap terbuka', 'header dikirim bertahap selama 90 detik', 'bandwidth rendah', 'alert Slow HTTP, incomplete header, dan long-lived connection'],
                'alert_groups' => [
                    ['count' => 36, 'message' => 'SIMULATION Possible Slow HTTP or Slowloris traffic', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 24, 'message' => 'SIMULATION Repeated incomplete HTTP header', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 18, 'message' => 'SIMULATION Long-lived HTTP connection', 'priority' => 1, 'protocol' => 'TCP', 'port' => 80],
                ],
            ],
            'slowloris-suspicious' => [
                'label' => 'Slow HTTP Incomplete Evidence', 'profile' => 'slowloris', 'attack_pattern' => 'slow_http',
                'expected_decision' => 'suspicious',
                'indicators' => ['8 koneksi HTTP long-lived', 'header bertahap', 'alert sedikit dan tidak cukup untuk evidence gate penuh'],
                'alert_groups' => [
                    ['count' => 8, 'message' => 'SIMULATION Possible Slow HTTP incomplete evidence', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                ],
            ],
            'loic' => [
                'label' => 'LOIC HTTP/TCP/UDP Flood', 'profile' => 'loic', 'attack_pattern' => 'http_flood',
                'expected_decision' => 'attack_detected',
                'indicators' => ['360 koneksi HTTP singkat', 'request rate tinggi', 'burst TCP dan UDP', 'alert HTTP flood dan connection flood'],
                'alert_groups' => [
                    ['count' => 90, 'message' => 'SIMULATION LOIC HTTP flood pattern', 'priority' => 1, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 60, 'message' => 'SIMULATION TCP connection flood threshold', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 40, 'message' => 'SIMULATION UDP flood burst', 'priority' => 2, 'protocol' => 'UDP', 'port' => 80],
                ],
            ],
            'hoic' => [
                'label' => 'HOIC Multi-path HTTP Flood', 'profile' => 'hoic', 'attack_pattern' => 'http_flood',
                'expected_decision' => 'attack_detected',
                'indicators' => ['150 HTTP request pada beberapa URI', 'banyak koneksi singkat', 'respons HTTP berulang', 'alert high-rate HTTP flood dan multi-path request'],
                'alert_groups' => [
                    ['count' => 120, 'message' => 'SIMULATION HOIC high-rate HTTP flood', 'priority' => 1, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 80, 'message' => 'SIMULATION Repeated multi-path HTTP request', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                ],
            ],
            'hping3' => [
                'label' => 'Hping3 TCP SYN / UDP / ICMP Flood', 'profile' => 'hping3', 'attack_pattern' => 'tcp_syn_flood',
                'expected_decision' => 'attack_detected',
                'indicators' => ['1.200 TCP SYN tanpa handshake lengkap', '1.000 UDP datagram', '800 ICMP echo request', 'alert transport flood per protokol'],
                'alert_groups' => [
                    ['count' => 250, 'message' => 'SIMULATION Hping3 TCP SYN flood', 'priority' => 1, 'protocol' => 'TCP', 'port' => 443],
                    ['count' => 180, 'message' => 'SIMULATION Hping3 UDP flood', 'priority' => 2, 'protocol' => 'UDP', 'port' => 33434],
                    ['count' => 120, 'message' => 'SIMULATION Hping3 ICMP flood', 'priority' => 2, 'protocol' => 'ICMP', 'port' => 0],
                ],
            ],
            'torshammer' => [
                'label' => 'Torshammer Slow HTTP POST', 'profile' => 'torshammer', 'attack_pattern' => 'slow_http',
                'expected_decision' => 'attack_detected',
                'indicators' => ['60 POST Content-Length besar', 'body sangat lambat selama 80 detik', 'koneksi HTTP tetap terbuka', 'alert slow POST, slow read, dan long-lived connection'],
                'alert_groups' => [
                    ['count' => 60, 'message' => 'SIMULATION Torshammer slow HTTP POST body', 'priority' => 1, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 48, 'message' => 'SIMULATION HTTP request body delivered slowly', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 36, 'message' => 'SIMULATION Slow read long-lived connection', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                ],
            ],
            'xerxes' => [
                'label' => 'Xerxes Keep-alive HTTP Flood', 'profile' => 'xerxes', 'attack_pattern' => 'http_flood',
                'expected_decision' => 'attack_detected',
                'indicators' => ['300 koneksi keep-alive', 'request HTTP berulang per koneksi', 'packet dan connection volume tinggi', 'alert connection flood dan HTTP flood'],
                'alert_groups' => [
                    ['count' => 110, 'message' => 'SIMULATION Xerxes connection flood', 'priority' => 1, 'protocol' => 'TCP', 'port' => 80],
                    ['count' => 90, 'message' => 'SIMULATION Xerxes keep-alive HTTP flood', 'priority' => 1, 'protocol' => 'TCP', 'port' => 80],
                ],
            ],
            'http-burst' => [
                'label' => 'HTTP Burst Baseline', 'profile' => 'slowloris', 'attack_pattern' => 'normal_baseline',
                'expected_decision' => 'normal',
                'indicators' => ['100 request HTTP singkat', 'semua koneksi ditutup', 'tanpa koneksi long-lived', 'alert baseline hanya untuk pembanding false-positive'],
                'alert_groups' => [
                    ['count' => 12, 'message' => 'SIMULATION HTTP burst baseline observed', 'priority' => 3, 'protocol' => 'TCP', 'port' => 80],
                ],
            ],
            'portscan' => [
                'label' => 'TCP Portscan Baseline', 'profile' => 'hping3', 'attack_pattern' => 'portscan',
                'expected_decision' => 'normal',
                'indicators' => ['32 SYN pada port berbeda', 'koneksi tidak menetap', 'tanpa HTTP flood', 'alert TCP port scan'],
                'alert_groups' => [
                    ['count' => 32, 'message' => 'SIMULATION TCP port scan pattern', 'priority' => 2, 'protocol' => 'TCP', 'port' => 80],
                ],
            ],
            'iperf-bandwidth' => [
                'label' => 'iPerf Bandwidth Baseline', 'profile' => 'hping3', 'attack_pattern' => 'normal_baseline',
                'expected_decision' => 'normal',
                'indicators' => ['satu stream TCP port 5201', 'payload besar', 'tanpa HTTP', 'alert baseline throughput untuk pembanding false-positive'],
                'alert_groups' => [
                    ['count' => 8, 'message' => 'SIMULATION iPerf bandwidth baseline observed', 'priority' => 3, 'protocol' => 'TCP', 'port' => 5201],
                ],
            ],
        ];
    }

    public function generate(string $outputDirectory, ?string $scenarioKey = null): array
    {
        $outputDirectory = rtrim($outputDirectory, DIRECTORY_SEPARATOR);
        $this->ensureDirectory($outputDirectory);
        $manifest = ['is_simulated' => true, 'generation_mode' => 'offline_packet_synthesis', 'scenarios' => []];
        $scenarios = $this->scenarios();
        if ($scenarioKey !== null) {
            if (!isset($scenarios[$scenarioKey])) {
                throw new InvalidArgumentException("Unknown scenario: {$scenarioKey}");
            }
            $scenarios = [$scenarioKey => $scenarios[$scenarioKey]];
        }

        foreach ($scenarios as $key => $scenario) {
            $this->packets = [];
            $this->ipId = 1;
            $this->buildScenario($key);

            $pcap = "{$outputDirectory}/{$key}-acquisition.pcapng";
            $log = "{$outputDirectory}/{$key}-validation.log";
            $metadata = "{$outputDirectory}/{$key}-metadata.json";
            $this->writePcapng($pcap);
            $alertCount = $this->writeSnortFastLog($log, $scenario['alert_groups']);
            $summary = [
                'is_simulated' => true,
                'generation_mode' => 'offline_packet_synthesis',
                'scenario_key' => $key,
                'label' => $scenario['label'],
                'tool_profile' => $scenario['profile'],
                'attack_pattern' => $scenario['attack_pattern'],
                'expected_decision' => $scenario['expected_decision'],
                'source_ip' => self::SOURCE_IP,
                'target_ip' => self::TARGET_IP,
                'indicators' => $scenario['indicators'],
                'packet_count' => count($this->packets),
                'alert_count' => $alertCount,
                'acquisition_file' => basename($pcap),
                'validation_file' => basename($log),
                'sha256' => ['acquisition' => hash_file('sha256', $pcap), 'validation' => hash_file('sha256', $log)],
            ];
            file_put_contents($metadata, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
            $manifest['scenarios'][$key] = $summary;
        }

        file_put_contents("{$outputDirectory}/MANIFEST.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $manifest;
    }

    private function buildScenario(string $key): void
    {
        match ($key) {
            'slowloris' => $this->slowHttp(false),
            'slowloris-suspicious' => $this->slowHttp(false, true),
            'torshammer' => $this->slowHttp(true),
            'loic' => $this->httpFlood(360, 0.015, true),
            'hoic' => $this->httpFlood(150, 0.012, true, true),
            'xerxes' => $this->httpFlood(300, 0.018, true, false, 3),
            'hping3' => $this->transportFlood(),
            'http-burst' => $this->httpFlood(100, 0.02, true),
            'portscan' => $this->portScan(),
            'iperf-bandwidth' => $this->iperf(),
        };
    }

    private function slowHttp(bool $post, bool $incompleteEvidence = false): void
    {
        $count = $post ? 60 : ($incompleteEvidence ? 8 : 36);
        for ($i = 0; $i < $count; $i++) {
            $start = self::START_EPOCH + ($i * 0.08);
            $port = 40000 + $i;
            $payloads = $post
                ? [[0.02, "POST /upload HTTP/1.1\r\nHost: lab.local\r\nContent-Length: 65535\r\nConnection: keep-alive\r\n\r\n"], [20.0, 'a'], [45.0, 'b'], [80.0, 'c']]
                : [[0.02, "GET /slow/{$i} HTTP/1.1\r\nHost: lab.local\r\nX-Lab: open\r\n"], [18.0, "X-Fragment: {$i}\r\n"], [45.0, "X-Wait: 45\r\n"], [90.0, "X-Hold: true\r\n"]];
            $this->tcpFlow($start, $port, 80, $payloads, null, false);
        }
    }

    private function httpFlood(int $count, float $step, bool $close, bool $multiPath = false, int $requestsPerFlow = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $start = self::START_EPOCH + ($i * $step);
            $payloads = [];
            for ($request = 0; $request < $requestsPerFlow; $request++) {
                $uri = $multiPath ? ['/api/a', '/api/b', '/api/c'][$i % 3] : '/';
                $payloads[] = [0.02 + ($request * 0.01), "GET {$uri}?n={$i}&r={$request} HTTP/1.1\r\nHost: lab.local\r\nConnection: ".($close ? 'close' : 'keep-alive')."\r\n\r\n"];
            }
            $response = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\nConnection: ".($close ? 'close' : 'keep-alive')."\r\n\r\nOK";
            $this->tcpFlow($start, 41000 + $i, 80, $payloads, $response, $close);
        }

        if ($count === 360) {
            for ($i = 0; $i < 100; $i++) {
                $this->udp(self::START_EPOCH + 3 + ($i * 0.005), 50000 + $i, 80, str_repeat('L', 48));
            }
        }
    }

    private function transportFlood(): void
    {
        for ($i = 0; $i < 1200; $i++) {
            $this->tcp(self::START_EPOCH + ($i * 0.002), 52000 + $i, 443, 1000 + $i, 0, 0x002);
        }
        for ($i = 0; $i < 1000; $i++) {
            $this->udp(self::START_EPOCH + 1 + ($i * 0.002), 53000 + $i, 33434, str_repeat('U', 64));
        }
        for ($i = 0; $i < 800; $i++) {
            $this->icmp(self::START_EPOCH + 2 + ($i * 0.002), $i);
        }
    }

    private function portScan(): void
    {
        foreach (range(1, 32) as $offset) {
            $port = 20 + $offset;
            $time = self::START_EPOCH + ($offset * 0.03);
            $sourcePort = 54000 + $offset;
            $this->tcp($time, $sourcePort, $port, 2000 + $offset, 0, 0x002);
            $this->tcp($time + 0.002, $port, $sourcePort, 8000 + $offset, 2001 + $offset, 0x014, self::TARGET_IP, self::SOURCE_IP);
        }
    }

    private function iperf(): void
    {
        $this->tcpFlow(self::START_EPOCH, 55000, 5201, [], null, false);
        for ($i = 0; $i < 300; $i++) {
            $this->tcp(self::START_EPOCH + 0.02 + ($i * 0.003), 55000, 5201, 9000 + ($i * 1200), 1, 0x018, self::SOURCE_IP, self::TARGET_IP, str_repeat('I', 1200));
        }
    }

    /** @param list<array{0: float, 1: string}> $payloads */
    private function tcpFlow(float $start, int $sourcePort, int $destinationPort, array $payloads, ?string $response, bool $close): void
    {
        $clientSeq = 10000 + $sourcePort;
        $serverSeq = 50000 + $sourcePort;
        $this->tcp($start, $sourcePort, $destinationPort, $clientSeq, 0, 0x002);
        $this->tcp($start + 0.002, $destinationPort, $sourcePort, $serverSeq, $clientSeq + 1, 0x012, self::TARGET_IP, self::SOURCE_IP);
        $this->tcp($start + 0.004, $sourcePort, $destinationPort, $clientSeq + 1, $serverSeq + 1, 0x010);
        $clientSeq++;
        $last = $start + 0.004;
        foreach ($payloads as [$offset, $payload]) {
            $time = $start + $offset;
            $this->tcp($time, $sourcePort, $destinationPort, $clientSeq, $serverSeq + 1, 0x018, self::SOURCE_IP, self::TARGET_IP, $payload);
            $clientSeq += strlen($payload);
            $last = $time;
        }
        if ($response !== null) {
            $this->tcp($last + 0.003, $destinationPort, $sourcePort, $serverSeq + 1, $clientSeq, 0x018, self::TARGET_IP, self::SOURCE_IP, $response);
            $serverSeq += strlen($response);
            $last += 0.003;
        }
        if ($close) {
            $this->tcp($last + 0.004, $sourcePort, $destinationPort, $clientSeq, $serverSeq + 1, 0x011);
            $this->tcp($last + 0.006, $destinationPort, $sourcePort, $serverSeq + 1, $clientSeq + 1, 0x011, self::TARGET_IP, self::SOURCE_IP);
        }
    }

    private function tcp(float $time, int $sourcePort, int $destinationPort, int $sequence, int $acknowledgement, int $flags, string $source = self::SOURCE_IP, string $destination = self::TARGET_IP, string $payload = ''): void
    {
        $header = pack('nnNNnnnn', $sourcePort, $destinationPort, $sequence, $acknowledgement, 0x5000 | $flags, 64240, 0, 0);
        $segment = $header.$payload;
        $pseudo = inet_pton($source).inet_pton($destination).pack('CCn', 0, 6, strlen($segment));
        $segment = substr_replace($segment, pack('n', $this->checksum($pseudo.$segment)), 16, 2);
        $this->packet($time, $source, $destination, 6, $segment);
    }

    private function udp(float $time, int $sourcePort, int $destinationPort, string $payload): void
    {
        $segment = pack('nnnn', $sourcePort, $destinationPort, 8 + strlen($payload), 0).$payload;
        $this->packet($time, self::SOURCE_IP, self::TARGET_IP, 17, $segment);
    }

    private function icmp(float $time, int $sequence): void
    {
        $body = pack('CCnnn', 8, 0, 0, 1, $sequence).str_repeat('P', 32);
        $body = substr_replace($body, pack('n', $this->checksum($body)), 2, 2);
        $this->packet($time, self::SOURCE_IP, self::TARGET_IP, 1, $body);
    }

    private function packet(float $time, string $source, string $destination, int $protocol, string $payload): void
    {
        $ip = pack('CCnnnCCn', 0x45, 0, 20 + strlen($payload), $this->ipId++, 0x4000, 64, $protocol, 0)
            .inet_pton($source).inet_pton($destination);
        $ip = substr_replace($ip, pack('n', $this->checksum($ip)), 10, 2).$payload;
        $ethernet = hex2bin('0200000000200200000000100800').$ip;
        $this->packets[] = ['time' => $time, 'frame' => $ethernet];
    }

    private function writePcapng(string $path): void
    {
        usort($this->packets, fn (array $a, array $b): int => $a['time'] <=> $b['time']);
        $data = $this->block(0x0A0D0D0A, pack('VvvVV', 0x1A2B3C4D, 1, 0, 0xFFFFFFFF, 0xFFFFFFFF));
        $data .= $this->block(0x00000001, pack('vvV', 1, 0, 65535));
        foreach ($this->packets as $packet) {
            $timestamp = (int) round($packet['time'] * 1000000);
            $high = intdiv($timestamp, 4294967296);
            $low = $timestamp % 4294967296;
            $frame = $packet['frame'];
            $data .= $this->block(0x00000006, pack('VVVVV', 0, $high, $low, strlen($frame), strlen($frame)).$frame);
        }
        file_put_contents($path, $data);
    }

    /** @param list<array{count: int, message: string, priority: int, protocol: string, port: int}> $groups */
    private function writeSnortFastLog(string $path, array $groups): int
    {
        $lines = ['# SIMULATION ONLY: offline-generated validation evidence; not Snort output from a live target.'];
        $count = 0;
        foreach ($groups as $groupIndex => $group) {
            for ($i = 0; $i < $group['count']; $i++) {
                $second = str_pad((string) (($count + $i) % 60), 2, '0', STR_PAD_LEFT);
                $sourcePort = 40000 + (($count + $i) % 2000);
                $destination = $group['port'] === 0 ? '0' : (string) $group['port'];
                $lines[] = "08/20-09:00:{$second}.000  [**] [1:".(900000 + $groupIndex).":1] {$group['message']} [**] [Classification: Simulation] [Priority: {$group['priority']}] {{$group['protocol']}} ".self::SOURCE_IP.":{$sourcePort} -> ".self::TARGET_IP.":{$destination}";
            }
            $count += $group['count'];
        }
        file_put_contents($path, implode("\n", $lines)."\n");

        return $count;
    }

    private function block(int $type, string $body): string
    {
        $padding = (4 - (strlen($body) % 4)) % 4;
        $length = 12 + strlen($body) + $padding;

        return pack('VV', $type, $length).$body.str_repeat("\0", $padding).pack('V', $length);
    }

    private function checksum(string $data): int
    {
        if (strlen($data) % 2 === 1) {
            $data .= "\0";
        }
        $sum = array_sum(unpack('n*', $data));
        while ($sum >> 16) {
            $sum = ($sum & 0xFFFF) + ($sum >> 16);
        }

        return (~$sum) & 0xFFFF;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException("Cannot create output directory: {$path}");
        }
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $root = dirname(__DIR__, 2);
    $output = $argv[1] ?? "{$root}/storage/app/simulated-lab";
    $scenario = $argv[2] ?? null;
    $manifest = (new OfflineLabArtifactGenerator())->generate($output, $scenario);

    echo "SIMULATION_ARTIFACTS_CREATED\n";
    foreach ($manifest['scenarios'] as $key => $scenario) {
        echo "{$key}: {$scenario['packet_count']} packets, {$scenario['alert_count']} validation alerts\n";
    }
}
