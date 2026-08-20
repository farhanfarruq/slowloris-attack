<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Esp32TargetService
{
    private const METRIC_FIELDS = [
        'uptime_seconds',
        'free_heap_bytes',
        'min_free_heap_bytes',
        'request_count',
        'reset_reason',
    ];

    public function readiness(bool $requireCaptureInterface = true): array
    {
        $this->assertAllowedTarget();

        if ($requireCaptureInterface) {
            $this->assertCaptureInterface();
        }

        $this->health();

        return [
            'ready' => true,
            'checked_at' => now()->toIso8601String(),
            'target' => $this->identity(),
            'metrics' => $this->metrics(),
        ];
    }

    public function health(): void
    {
        try {
            $response = Http::connectTimeout($this->timeout())
                ->timeout($this->timeout())
                ->get($this->url((string) config('esp32.health_path')));
        } catch (ConnectionException $e) {
            throw new RuntimeException('ESP32_NOT_CONNECTED: target tidak dapat dihubungi.', 0, $e);
        }

        if (! $response->successful() || trim($response->body()) !== 'OK') {
            throw new RuntimeException('ESP32_HEALTH_FAILED: /health harus mengembalikan HTTP 200 dan body OK.');
        }
    }

    public function metrics(): array
    {
        try {
            $response = Http::connectTimeout($this->timeout())
                ->timeout($this->timeout())
                ->acceptJson()
                ->get($this->url((string) config('esp32.metrics_path')));
        } catch (ConnectionException $e) {
            throw new RuntimeException('ESP32_METRICS_FAILED: target tidak dapat dihubungi.', 0, $e);
        }

        $metrics = $response->successful() ? $response->json() : null;
        if (! is_array($metrics)) {
            throw new RuntimeException('ESP32_METRICS_FAILED: /metrics harus mengembalikan JSON dengan HTTP 200.');
        }

        foreach (self::METRIC_FIELDS as $field) {
            if (! array_key_exists($field, $metrics) || $metrics[$field] === null) {
                throw new RuntimeException("ESP32_METRICS_FAILED: field {$field} tidak tersedia.");
            }
        }

        foreach (array_slice(self::METRIC_FIELDS, 0, 4) as $field) {
            if (! is_numeric($metrics[$field])) {
                throw new RuntimeException("ESP32_METRICS_FAILED: field {$field} harus numerik.");
            }
            $metrics[$field] = (int) $metrics[$field];
        }

        if (! is_string($metrics['reset_reason']) || trim($metrics['reset_reason']) === '') {
            throw new RuntimeException('ESP32_METRICS_FAILED: field reset_reason harus berupa teks.');
        }

        return array_intersect_key($metrics, array_flip(self::METRIC_FIELDS));
    }

    public function assertAllowedTarget(): void
    {
        $host = (string) config('esp32.host');
        $allowed = array_map('strval', (array) config('esp32.allowed_hosts', []));
        $scheme = (string) config('esp32.scheme');
        $port = (int) config('esp32.port');
        $expectedFilter = "host {$host} and tcp port {$port}";
        $isPublicIp = filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;

        if (config('esp32.type') !== 'esp32'
            || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
            || $isPublicIp
            || ! in_array($host, $allowed, true)
            || $scheme !== 'http'
            || $port < 1
            || $port > 65535
            || config('esp32.capture_filter') !== $expectedFilter) {
            throw new RuntimeException('TARGET_NOT_ALLOWED: hanya target IPv4 ESP32 lab yang dikonfigurasi yang dapat digunakan.');
        }
    }

    public function assertCaptureInterface(): void
    {
        $interface = (string) config('esp32.capture_interface');
        if (! preg_match('/^[A-Za-z0-9_.:-]+$/', $interface)
            || ! is_dir('/sys/class/net/'.$interface)) {
            throw new RuntimeException("CAPTURE_INTERFACE_NOT_FOUND: interface {$interface} tidak tersedia pada host ini.");
        }
    }

    public function identity(): array
    {
        return [
            'type' => 'esp32',
            'host' => (string) config('esp32.host'),
            'port' => (int) config('esp32.port'),
            'scheme' => (string) config('esp32.scheme'),
            'capture_interface' => (string) config('esp32.capture_interface'),
            'serial_port' => (string) config('esp32.serial_port'),
            'ssid' => (string) config('esp32.ssid'),
            'device' => (array) config('esp32.device'),
        ];
    }

    private function url(string $path): string
    {
        $this->assertAllowedTarget();
        $path = '/'.ltrim($path, '/');

        return sprintf(
            '%s://%s:%d%s',
            config('esp32.scheme'),
            config('esp32.host'),
            config('esp32.port'),
            $path,
        );
    }

    private function timeout(): int
    {
        return max(1, min(10, (int) config('esp32.http_timeout_seconds', 3)));
    }
}
