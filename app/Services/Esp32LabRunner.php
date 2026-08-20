<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class Esp32LabRunner
{
    public function __construct(
        private Esp32TargetService $target,
        private AcquisitionParser $acquisitionParser,
    ) {}

    public function run(string $experimentCode, int $durationSeconds): array
    {
        $this->assertExperimentCode($experimentCode);
        if ($durationSeconds < 1 || $durationSeconds > 86400) {
            throw new RuntimeException('INVALID_CAPTURE_DURATION: durasi harus 1-86400 detik.');
        }

        $readiness = $this->target->readiness();
        $this->assertHostTools();

        $paths = $this->artifactPaths($experimentCode);
        foreach ($paths as $path) {
            File::ensureDirectoryExists(dirname($path));
            if (is_file($path)) {
                throw new RuntimeException('ARTIFACT_EXISTS: '.$this->relativePath($path));
            }
        }

        $captureStartedAt = now();
        $dumpcap = new Process($this->captureCommand($paths['pcap']), base_path());
        $dumpcap->setTimeout(null);
        $dumpcap->start();
        usleep(300_000);

        if (! $dumpcap->isRunning()) {
            throw new RuntimeException('DUMPCAP_START_FAILED: '.trim($dumpcap->getErrorOutput()));
        }

        try {
            sleep($durationSeconds);
        } finally {
            if ($dumpcap->isRunning()) {
                $dumpcap->signal(2);
                $dumpcap->wait();
            }
        }

        $captureEndedAt = now();
        if (! is_file($paths['pcap']) || filesize($paths['pcap']) === 0) {
            throw new RuntimeException('PCAP_EMPTY: dumpcap tidak menghasilkan PCAP berisi data.');
        }

        $pcapSummary = $this->acquisitionParser->parse($paths['pcap'], 'pcapng');
        if (($pcapSummary['parsed_summary']['parser'] ?? null) !== 'tshark-fields-stream') {
            throw new RuntimeException('TSHARK_FAILED: PCAP tidak dapat divalidasi dengan TShark.');
        }

        $metricsAfter = null;
        $metricsAfterError = null;
        try {
            $metricsAfter = $this->target->metrics();
        } catch (RuntimeException $e) {
            $metricsAfterError = $e->getMessage();
        }

        $snort = new Process($this->snortCommand($paths['pcap'], dirname($paths['alert'])), base_path());
        $snort->setTimeout(120);
        $snort->run();
        File::put($paths['snort_log'], $snort->getOutput().$snort->getErrorOutput());

        if (! $snort->isSuccessful()) {
            throw new RuntimeException('SNORT_ANALYSIS_FAILED: exit code '.$snort->getExitCode());
        }

        if (! is_file($paths['alert'])) {
            File::put($paths['alert'], '');
        }

        $alertCount = count(array_filter(file($paths['alert'], FILE_IGNORE_NEW_LINES) ?: [], 'strlen'));
        $dumpcapOutput = $dumpcap->getOutput().$dumpcap->getErrorOutput();
        preg_match('/Packets dropped:\s*(\d+)/i', $dumpcapOutput, $droppedMatch);

        $metadata = [
            'experiment_id' => $experimentCode,
            'target_type' => 'esp32',
            'target_ip' => config('esp32.host'),
            'target_port' => (int) config('esp32.port'),
            'esp32_identity' => $readiness['target']['device'],
            'capture_interface' => config('esp32.capture_interface'),
            'pcap_file' => $this->relativePath($paths['pcap']),
            'pcap_sha256' => hash_file('sha256', $paths['pcap']),
            'capture_start' => $captureStartedAt->toIso8601String(),
            'capture_end' => $captureEndedAt->toIso8601String(),
            'packet_count' => $pcapSummary['total_packets'],
            'dropped_packets' => isset($droppedMatch[1]) ? (int) $droppedMatch[1] : null,
            'snort_version' => $this->version((string) config('esp32.snort_binary'), ['-V'], '/Version\s+([\d.]+)/'),
            'libdaq_version' => $this->version((string) config('esp32.snort_binary'), ['-V'], '/DAQ version\s+([\d.]+)/'),
            'snort_exit_code' => $snort->getExitCode(),
            'snort_log' => $this->relativePath($paths['snort_log']),
            'snort_log_sha256' => hash_file('sha256', $paths['snort_log']),
            'alert_file' => $this->relativePath($paths['alert']),
            'alert_file_sha256' => hash_file('sha256', $paths['alert']),
            'alert_count' => $alertCount,
            'analysis_success' => true,
            'esp32_metrics_before' => $readiness['metrics'],
            'esp32_metrics_after' => $metricsAfter,
            'esp32_metrics_after_error' => $metricsAfterError,
            'metric_deltas' => $this->metricDeltas($readiness['metrics'], $metricsAfter),
            'pcap_summary' => $pcapSummary,
            'created_at' => now()->toIso8601String(),
        ];

        File::put(
            $paths['metadata'],
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
        );

        return $metadata + ['metadata_file' => $this->relativePath($paths['metadata'])];
    }

    public function captureCommand(string $pcapPath): array
    {
        return [
            (string) config('esp32.dumpcap_binary'),
            '-i', (string) config('esp32.capture_interface'),
            '-f', (string) config('esp32.capture_filter'),
            '-w', $pcapPath,
        ];
    }

    public function snortCommand(string $pcapPath, string $logDirectory): array
    {
        return [
            (string) config('esp32.snort_binary'),
            '-q',
            '-c', (string) config('esp32.snort_config'),
            '-r', $pcapPath,
            '-A', 'alert_fast',
            '-l', $logDirectory,
            '--lua', 'alert_fast = { file = true }',
        ];
    }

    private function artifactPaths(string $experimentCode): array
    {
        return [
            'pcap' => base_path("captures/experiment/{$experimentCode}.pcapng"),
            'snort_log' => base_path("logs/snort/experiment/{$experimentCode}.log"),
            'alert' => base_path("logs/snort/experiment/{$experimentCode}/alert_fast.txt"),
            'metadata' => base_path("metadata/experiment/{$experimentCode}.json"),
        ];
    }

    private function assertHostTools(): void
    {
        foreach (['dumpcap_binary', 'tshark_binary', 'snort_binary'] as $key) {
            $path = (string) config("esp32.{$key}");
            if (! is_file($path) || ! is_executable($path)) {
                $code = $key === 'snort_binary' ? 'SNORT_NOT_FOUND' : strtoupper(str_replace('_binary', '_NOT_FOUND', $key));
                throw new RuntimeException("{$code}: {$path}");
            }
        }

        $snortConfig = (string) config('esp32.snort_config');
        if (! is_file($snortConfig)) {
            throw new RuntimeException("SNORT_CONFIG_INVALID: {$snortConfig}");
        }
    }

    private function version(string $binary, array $arguments, string $pattern): ?string
    {
        $process = new Process([$binary, ...$arguments]);
        $process->run();
        preg_match($pattern, $process->getOutput().$process->getErrorOutput(), $matches);

        return $matches[1] ?? null;
    }

    private function metricDeltas(array $before, ?array $after): ?array
    {
        if ($after === null) {
            return null;
        }

        return [
            'delta_request_count' => $after['request_count'] - $before['request_count'],
            'delta_free_heap' => $after['free_heap_bytes'] - $before['free_heap_bytes'],
            'reset_changed' => $after['reset_reason'] !== $before['reset_reason']
                || $after['uptime_seconds'] < $before['uptime_seconds'],
        ];
    }

    private function assertExperimentCode(string $experimentCode): void
    {
        if (! preg_match('/^EXP-[0-9]{3,}$/', $experimentCode)) {
            throw new RuntimeException('INVALID_EXPERIMENT_CODE: gunakan format EXP-001.');
        }
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
    }
}
