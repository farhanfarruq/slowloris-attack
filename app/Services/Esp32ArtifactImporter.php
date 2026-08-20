<?php

namespace App\Services;

use App\Models\AcquisitionFile;
use App\Models\Experiment;
use App\Models\SnortAlert;
use App\Models\ValidationFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Esp32ArtifactImporter
{
    public function __construct(
        private AcquisitionParser $acquisitionParser,
        private ValidationParser $validationParser,
        private AuditService $audit,
    ) {}

    public function import(string $metadataPath): array
    {
        $metadataFile = $this->resolveProjectFile($metadataPath);
        $metadata = json_decode(file_get_contents($metadataFile), true, flags: JSON_THROW_ON_ERROR);
        $this->validateMetadata($metadata);

        $experiment = Experiment::where('experiment_code', $metadata['experiment_id'])->firstOrFail();
        if ($experiment->target_platform !== 'esp32') {
            throw new RuntimeException('TARGET_PROVENANCE_MISMATCH: record VM historis tidak boleh diubah menjadi bukti ESP32.');
        }

        if (($experiment->runtime_metadata['pcap_sha256'] ?? null) === $metadata['pcap_sha256']) {
            return ['already_imported' => true, 'experiment' => $experiment, 'metadata' => $metadata];
        }

        $pcapSource = $this->resolveProjectFile($metadata['pcap_file']);
        $alertSource = $this->resolveProjectFile($metadata['alert_file']);
        $snortLogSource = $this->resolveProjectFile($metadata['snort_log']);
        $this->assertHash($pcapSource, $metadata['pcap_sha256'], 'PCAP_HASH_MISMATCH');
        $this->assertHash($alertSource, $metadata['alert_file_sha256'], 'ALERT_HASH_MISMATCH');
        $this->assertHash($snortLogSource, $metadata['snort_log_sha256'], 'SNORT_LOG_HASH_MISMATCH');

        $acquisitionStored = 'acquisition/'.$experiment->id.'/'.basename($pcapSource);
        $validationStored = 'validation/'.$experiment->id.'/'.$experiment->experiment_code.'_alert_fast.txt';
        $written = [];

        try {
            $this->copyToStorage($pcapSource, $acquisitionStored);
            $written[] = $acquisitionStored;
            $this->copyToStorage($alertSource, $validationStored);
            $written[] = $validationStored;

            $result = DB::transaction(function () use (
                $experiment,
                $metadata,
                $acquisitionStored,
                $validationStored,
            ): array {
                $pcapAbsolute = Storage::disk('local')->path($acquisitionStored);
                $pcap = $this->acquisitionParser->parse($pcapAbsolute, 'pcapng');
                $captureLabel = strtolower($experiment->experiment_code).'-esp32';

                $acquisition = AcquisitionFile::create([
                    'experiment_id' => $experiment->id,
                    'original_name' => basename($metadata['pcap_file']),
                    'stored_name' => $acquisitionStored,
                    'extension' => 'pcapng',
                    'size_bytes' => filesize($pcapAbsolute),
                    'mime_type' => function_exists('mime_content_type') ? (mime_content_type($pcapAbsolute) ?: null) : null,
                    'capture_label' => $captureLabel,
                    'scenario_key' => $experiment->scenario_key,
                    'source_ip' => $experiment->source_ip,
                    'target_ip' => $metadata['target_ip'],
                    'capture_started_at' => $metadata['capture_start'],
                    'capture_ended_at' => $metadata['capture_end'],
                    'total_packets' => $pcap['total_packets'],
                    'tcp_packets' => $pcap['tcp_packets'],
                    'http_packets' => $pcap['http_packets'],
                    'avg_packet_size' => $pcap['avg_packet_size'],
                    'top_source_ips' => $pcap['top_source_ips'],
                    'top_destination_ips' => $pcap['top_destination_ips'],
                    'protocol_distribution' => $pcap['protocol_distribution'],
                    'total_connections' => $pcap['total_connections'],
                    'avg_connection_duration' => $pcap['avg_connection_duration'],
                    'half_open_connections' => $pcap['half_open_connections'],
                    'parsed_summary' => array_merge($pcap['parsed_summary'], [
                        'pcap_sha256' => $metadata['pcap_sha256'],
                        'dropped_packets' => $metadata['dropped_packets'],
                    ]),
                ]);

                $alertAbsolute = Storage::disk('local')->path($validationStored);
                $alerts = $this->validationParser->parse($alertAbsolute, 'txt');
                if ((int) $alerts['total_alerts'] !== (int) $metadata['alert_count']) {
                    throw new RuntimeException('ALERT_COUNT_MISMATCH: metadata tidak cocok dengan alert file.');
                }
                $validation = ValidationFile::create([
                    'experiment_id' => $experiment->id,
                    'acquisition_file_id' => $acquisition->id,
                    'original_name' => basename($metadata['alert_file']),
                    'stored_name' => $validationStored,
                    'extension' => 'txt',
                    'size_bytes' => filesize($alertAbsolute),
                    'capture_label' => $captureLabel,
                    'scenario_key' => $experiment->scenario_key,
                    'source_ip' => $experiment->source_ip,
                    'target_ip' => $metadata['target_ip'],
                    'snort_mode' => 'ids',
                    'rule_set' => basename((string) config('esp32.snort_config')),
                    'monitoring_interface' => $metadata['capture_interface'],
                    'notes' => 'Snort offline analysis; zero alert tetap merupakan hasil sukses.',
                    'total_alerts' => $alerts['total_alerts'],
                    'dominant_alert_type' => $alerts['dominant_alert_type'],
                    'highest_severity' => $alerts['highest_severity'],
                    'top_source_ips' => $alerts['top_source_ips'],
                    'top_destination_ports' => $alerts['top_destination_ports'],
                    'alert_timeline' => $alerts['alert_timeline'],
                    'matches_slow_http_pattern' => $alerts['matches_slow_http_pattern'],
                    'parsed_summary' => array_merge($alerts['parsed_summary'] ?? [], [
                        'severity_count' => $alerts['severity_count'],
                        'analysis_success' => (bool) $metadata['analysis_success'],
                        'snort_exit_code' => $metadata['snort_exit_code'],
                        'snort_log_sha256' => $metadata['snort_log_sha256'],
                        'alert_file_sha256' => $metadata['alert_file_sha256'],
                    ]),
                ]);

                $this->persistAlerts($experiment, $validation, $alerts['alerts']);

                $experiment->update([
                    'network_interface' => $metadata['capture_interface'],
                    'target_ip' => $metadata['target_ip'],
                    'capture_duration' => max(1, (int) round(
                        strtotime($metadata['capture_end']) - strtotime($metadata['capture_start'])
                    )),
                    'runtime_metadata' => $metadata,
                    'status' => 'validated',
                ]);

                $this->audit->log('experiment.esp32_artifacts_imported', $experiment, [
                    'pcap_sha256' => $metadata['pcap_sha256'],
                    'alert_count' => $metadata['alert_count'],
                ]);

                return compact('experiment', 'acquisition', 'validation') + [
                    'already_imported' => false,
                    'metadata' => $metadata,
                ];
            });

            return $result;
        } catch (\Throwable $e) {
            foreach ($written as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    private function validateMetadata(array $metadata): void
    {
        $required = [
            'experiment_id', 'target_type', 'target_ip', 'target_port', 'capture_interface',
            'pcap_file', 'pcap_sha256', 'capture_start', 'capture_end', 'snort_exit_code',
            'snort_log', 'snort_log_sha256', 'alert_file', 'alert_file_sha256',
            'alert_count', 'analysis_success', 'esp32_metrics_before',
        ];

        foreach ($required as $field) {
            if (! array_key_exists($field, $metadata)) {
                throw new RuntimeException("INVALID_ESP32_METADATA: field {$field} tidak tersedia.");
            }
        }

        foreach (['experiment_id', 'target_type', 'target_ip', 'capture_interface', 'pcap_file', 'pcap_sha256',
            'capture_start', 'capture_end', 'snort_log', 'snort_log_sha256', 'alert_file', 'alert_file_sha256'] as $field) {
            if (! is_string($metadata[$field]) || trim($metadata[$field]) === '') {
                throw new RuntimeException("INVALID_ESP32_METADATA: field {$field} harus berupa teks.");
            }
        }

        foreach (['pcap_sha256', 'snort_log_sha256', 'alert_file_sha256'] as $field) {
            if (! preg_match('/^[a-f0-9]{64}$/i', $metadata[$field])) {
                throw new RuntimeException("INVALID_ESP32_METADATA: field {$field} bukan SHA-256.");
            }
        }

        if (! preg_match('/^EXP-[0-9]{3,}$/', $metadata['experiment_id'])
            || ! is_array($metadata['esp32_metrics_before'])
            || ! is_numeric($metadata['target_port'])
            || ! is_numeric($metadata['snort_exit_code'])
            || ! is_numeric($metadata['alert_count'])
            || (int) $metadata['alert_count'] < 0
            || strtotime($metadata['capture_start']) === false
            || strtotime($metadata['capture_end']) === false) {
            throw new RuntimeException('INVALID_ESP32_METADATA: tipe data metadata tidak valid.');
        }

        if ($metadata['target_type'] !== 'esp32'
            || $metadata['target_ip'] !== config('esp32.host')
            || (int) $metadata['target_port'] !== (int) config('esp32.port')
            || $metadata['capture_interface'] !== config('esp32.capture_interface')
            || $metadata['analysis_success'] !== true
            || (int) $metadata['snort_exit_code'] !== 0) {
            throw new RuntimeException('INVALID_ESP32_METADATA: identitas target atau status analisis tidak valid.');
        }

        $this->validateMetrics($metadata['esp32_metrics_before']);
        if (($metadata['esp32_metrics_after'] ?? null) !== null) {
            if (! is_array($metadata['esp32_metrics_after'])) {
                throw new RuntimeException('INVALID_ESP32_METADATA: metrics after harus object atau null.');
            }
            $this->validateMetrics($metadata['esp32_metrics_after']);
        }
    }

    private function persistAlerts(Experiment $experiment, ValidationFile $validation, array $alerts): void
    {
        $rows = [];
        foreach (array_slice($alerts, 0, 1000) as $alert) {
            $rows[] = [
                'experiment_id' => $experiment->id,
                'validation_file_id' => $validation->id,
                'alert_timestamp' => $alert['timestamp']?->toDateTimeString(),
                'alert_type' => $alert['msg'],
                'severity' => $alert['severity'],
                'source_ip' => $alert['src_ip'],
                'source_port' => $alert['src_port'],
                'destination_ip' => $alert['dst_ip'],
                'destination_port' => $alert['dst_port'],
                'protocol' => $alert['protocol'],
                'message' => $alert['msg'],
                'raw' => json_encode($alert['raw'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            SnortAlert::insert($rows);
        }
    }

    private function copyToStorage(string $source, string $destination): void
    {
        if (Storage::disk('local')->exists($destination)) {
            throw new RuntimeException("IMPORT_DESTINATION_EXISTS: {$destination}");
        }

        $stream = fopen($source, 'rb');
        try {
            if ($stream === false || ! Storage::disk('local')->writeStream($destination, $stream)) {
                throw new RuntimeException("ARTIFACT_IMPORT_FAILED: {$destination}");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function resolveProjectFile(string $relativePath): string
    {
        if ($relativePath === '' || str_starts_with($relativePath, '/') || str_contains($relativePath, "\0")) {
            throw new RuntimeException('INVALID_ARTIFACT_PATH: path harus relatif terhadap project.');
        }

        $root = realpath(base_path());
        $path = realpath(base_path($relativePath));
        if ($root === false || $path === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            throw new RuntimeException("INVALID_ARTIFACT_PATH: {$relativePath}");
        }

        return $path;
    }

    private function assertHash(string $path, string $expected, string $errorCode): void
    {
        if (! hash_equals(strtolower($expected), hash_file('sha256', $path))) {
            throw new RuntimeException("{$errorCode}: ".basename($path));
        }
    }

    private function validateMetrics(array $metrics): void
    {
        foreach (['uptime_seconds', 'free_heap_bytes', 'min_free_heap_bytes', 'request_count'] as $field) {
            if (! array_key_exists($field, $metrics) || ! is_numeric($metrics[$field])) {
                throw new RuntimeException("INVALID_ESP32_METADATA: metrics {$field} tidak valid.");
            }
        }

        if (! isset($metrics['reset_reason']) || ! is_string($metrics['reset_reason'])) {
            throw new RuntimeException('INVALID_ESP32_METADATA: metrics reset_reason tidak valid.');
        }
    }
}
