<?php

use App\Models\AiResult;
use App\Models\AcquisitionFile;
use App\Models\AuditLog;
use App\Models\Experiment;
use App\Models\ExtractedFeature;
use App\Models\FinalReport;
use App\Models\ReviewerNote;
use App\Models\SnortAlert;
use App\Models\User;
use App\Models\ValidationFile;
use App\Services\AcquisitionParser;
use App\Services\ValidationParser;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:purge-simulated {--force : Run without confirmation}', function () {
    if (!$this->option('force') && !$this->confirm('Hapus semua hasil AI simulasi/demo?')) {
        $this->warn('Dibatalkan.');
        return self::FAILURE;
    }

    $deleted = AiResult::query()
        ->where('is_simulated', true)
        ->orWhere('model_version', 'demo')
        ->orWhere('reason', 'like', 'Demo result:%')
        ->delete();

    $this->info("Hasil AI simulasi/demo dihapus: {$deleted}");
    return self::SUCCESS;
})->purpose('Remove simulated/demo AI validation results');

Artisan::command('ai:purge-failed {--force : Run without confirmation}', function () {
    if (!$this->option('force') && !$this->confirm('Hapus hasil AI gagal/Inconclusive 0% dari error provider?')) {
        $this->warn('Dibatalkan.');
        return self::FAILURE;
    }

    $deleted = AiResult::query()
        ->where('classification', 'Inconclusive')
        ->where('confidence_score', 0)
        ->where('reason', 'like', 'Provider tidak menghasilkan klasifikasi live:%')
        ->delete();

    $this->info("Hasil AI gagal dihapus: {$deleted}");
    return self::SUCCESS;
})->purpose('Remove failed live AI validation rows');

Artisan::command('lab:reset-research-data {--force : Run without confirmation}', function () {
    if (!$this->option('force') && !$this->confirm('Hapus semua data riset: experiment, akuisisi, validasi, alert, fitur, AI, laporan, dan file upload? Akun dan API key tetap disimpan.')) {
        $this->warn('Dibatalkan.');
        return self::FAILURE;
    }

    Storage::disk('local')->deleteDirectory('acquisition');
    Storage::disk('local')->deleteDirectory('validation');

    $counts = [
        'ai_results' => AiResult::query()->count(),
        'snort_alerts' => SnortAlert::query()->count(),
        'extracted_features' => ExtractedFeature::query()->count(),
        'validation_files' => ValidationFile::query()->count(),
        'acquisition_files' => AcquisitionFile::query()->count(),
        'final_reports' => FinalReport::query()->count(),
        'reviewer_notes' => ReviewerNote::query()->count(),
        'audit_logs' => AuditLog::query()->count(),
        'experiments' => Experiment::query()->count(),
    ];

    AiResult::query()->delete();
    SnortAlert::query()->delete();
    ExtractedFeature::query()->delete();
    ValidationFile::query()->delete();
    AcquisitionFile::query()->delete();
    FinalReport::query()->delete();
    ReviewerNote::query()->delete();
    AuditLog::query()->delete();
    Experiment::query()->delete();

    foreach ($counts as $table => $count) {
        $this->line("{$table}: {$count} dihapus");
    }

    $this->info('Data riset sudah kosong. Buat eksperimen baru dari website.');
    return self::SUCCESS;
})->purpose('Remove all research/demo data while keeping users and AI provider settings');

Artisan::command('lab:import-local-captures {--force : Replace imported records for the same scenarios}', function (
    AcquisitionParser $acquisitionParser,
    ValidationParser $validationParser,
) {
    $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
    if (!$admin) {
        $this->error('Tidak ada user admin. Jalankan seeder user dulu.');
        return self::FAILURE;
    }

    $baseDir = Storage::disk('local')->path('vm-lab-captures');
    if (!is_dir($baseDir)) {
        $baseDir = storage_path('app/vm-lab-captures');
    }
    $sourceIp = '192.168.56.102';
    $targetIp = '192.168.56.103';
    $iface = 'enp0s8';

    $scenarios = [
        [
            'key' => 'slow-http',
            'name' => 'Slow HTTP Headers - VM Wireshark Snort',
            'traffic_type' => 'slowloris_lab',
            'ground_truth_label' => 'slowloris_lab',
            'duration' => 100,
            'pcap' => 'slow-http-wireshark.pcapng',
            'snort' => 'slow-http-snort.log',
            'notes' => 'Data nyata dari VM lab: slowhttptest menuju Nginx target, capture Wireshark/dumpcap, validasi Snort.',
        ],
        [
            'key' => 'iperf-bandwidth',
            'name' => 'iPerf3 Bandwidth Baseline - VM Wireshark Snort',
            'traffic_type' => 'normal',
            'ground_truth_label' => 'normal',
            'duration' => 45,
            'pcap' => 'iperf-bandwidth-wireshark.pcapng',
            'snort' => 'iperf-bandwidth-snort.log',
            'notes' => 'Data nyata dari VM lab: traffic iPerf3 sebagai pembanding throughput tinggi, bukan Slowloris.',
        ],
        [
            'key' => 'http-burst',
            'name' => 'HTTP Burst Baseline - VM Wireshark Snort',
            'traffic_type' => 'normal',
            'ground_truth_label' => 'normal',
            'duration' => 45,
            'pcap' => 'http-burst-wireshark.pcapng',
            'snort' => 'http-burst-snort.log',
            'notes' => 'Data nyata dari VM lab: ApacheBench HTTP burst dengan capture Wireshark/dumpcap dan validasi Snort.',
        ],
        [
            'key' => 'portscan',
            'name' => 'Port Scan TCP Connect - VM Wireshark Snort',
            'traffic_type' => 'mixed',
            'ground_truth_label' => 'mixed',
            'duration' => 45,
            'pcap' => 'portscan-wireshark.pcapng',
            'snort' => 'portscan-snort.log',
            'notes' => 'Data nyata dari VM lab: Nmap TCP connect scan dengan capture Wireshark/dumpcap dan validasi Snort.',
        ],
    ];

    $imported = [];

    foreach ($scenarios as $scenario) {
        $pcapPath = "{$baseDir}/{$scenario['pcap']}";
        if (!is_file($pcapPath)) {
            $this->warn("Skip {$scenario['key']}: PCAP tidak ditemukan {$scenario['pcap']}");
            continue;
        }

        $snortPath = $scenario['snort'] ? "{$baseDir}/{$scenario['snort']}" : null;
        if ($scenario['snort'] && !is_file($snortPath)) {
            $this->warn("{$scenario['key']}: Snort log tidak ditemukan {$scenario['snort']}. Experiment dibuat akuisisi saja.");
            $snortPath = null;
        }

        $experiment = Experiment::where('scenario_key', $scenario['key'])->first();
        if ($experiment && $this->option('force')) {
            $experiment->delete();
            $experiment = null;
        }

        if (!$experiment) {
            $experiment = Experiment::create([
                'experiment_code' => nextExperimentCode(),
                'name' => $scenario['name'],
                'experiment_date' => Carbon::create(2026, 5, 29)->toDateString(),
                'network_interface' => $iface,
                'target_ip' => $targetIp,
                'source_ip' => $sourceIp,
                'capture_duration' => $scenario['duration'],
                'notes' => $scenario['notes'],
                'scenario_key' => $scenario['key'],
                'traffic_type' => $scenario['traffic_type'],
                'ground_truth_label' => $scenario['ground_truth_label'],
                'status' => 'created',
                'user_id' => $admin->id,
            ]);
        }

        $captureLabel = $scenario['key'] . '-20260529-vm-lab';
        $pcapExt = strtolower(pathinfo($scenario['pcap'], PATHINFO_EXTENSION));
        $pcapStored = "acquisition/{$experiment->id}/imported_{$scenario['pcap']}";
        Storage::disk('local')->put($pcapStored, file_get_contents($pcapPath));
        $pcapSummary = $acquisitionParser->parse(Storage::disk('local')->path($pcapStored), $pcapExt);

        $endedAt = Carbon::createFromTimestamp(filemtime($pcapPath));
        $acquisition = AcquisitionFile::updateOrCreate(
            [
                'experiment_id' => $experiment->id,
                'original_name' => $scenario['pcap'],
            ],
            [
                'stored_name' => $pcapStored,
                'extension' => $pcapExt,
                'size_bytes' => filesize($pcapPath),
                'mime_type' => 'application/octet-stream',
                'capture_label' => $captureLabel,
                'scenario_key' => $scenario['key'],
                'source_ip' => $sourceIp,
                'target_ip' => $targetIp,
                'capture_started_at' => $endedAt->copy()->subSeconds($scenario['duration']),
                'capture_ended_at' => $endedAt,
                'total_packets' => $pcapSummary['total_packets'],
                'tcp_packets' => $pcapSummary['tcp_packets'],
                'http_packets' => $pcapSummary['http_packets'],
                'avg_packet_size' => $pcapSummary['avg_packet_size'],
                'top_source_ips' => $pcapSummary['top_source_ips'],
                'top_destination_ips' => $pcapSummary['top_destination_ips'],
                'protocol_distribution' => $pcapSummary['protocol_distribution'],
                'total_connections' => $pcapSummary['total_connections'],
                'avg_connection_duration' => $pcapSummary['avg_connection_duration'],
                'half_open_connections' => $pcapSummary['half_open_connections'],
                'parsed_summary' => $pcapSummary['parsed_summary'],
            ],
        );

        $validation = null;
        if ($snortPath) {
            $snortExt = strtolower(pathinfo($scenario['snort'], PATHINFO_EXTENSION));
            $snortStored = "validation/{$experiment->id}/imported_{$scenario['snort']}";
            Storage::disk('local')->put($snortStored, file_get_contents($snortPath));
            $snortSummary = $validationParser->parse(Storage::disk('local')->path($snortStored), $snortExt);

            $validation = ValidationFile::updateOrCreate(
                [
                    'experiment_id' => $experiment->id,
                    'original_name' => $scenario['snort'],
                ],
                [
                    'acquisition_file_id' => $acquisition->id,
                    'stored_name' => $snortStored,
                    'extension' => $snortExt,
                    'size_bytes' => filesize($snortPath),
                    'capture_label' => $captureLabel,
                    'scenario_key' => $scenario['key'],
                    'source_ip' => $sourceIp,
                    'target_ip' => $targetIp,
                    'snort_mode' => 'ids',
                    'rule_set' => 'local lab rules',
                    'monitoring_interface' => $iface,
                    'threshold' => null,
                    'notes' => 'Diimpor dari storage/app/vm-lab-captures/' . $scenario['snort'],
                    'total_alerts' => $snortSummary['total_alerts'],
                    'dominant_alert_type' => $snortSummary['dominant_alert_type'],
                    'highest_severity' => $snortSummary['highest_severity'],
                    'top_source_ips' => $snortSummary['top_source_ips'],
                    'top_destination_ports' => $snortSummary['top_destination_ports'],
                    'alert_timeline' => $snortSummary['alert_timeline'],
                    'matches_slow_http_pattern' => $scenario['key'] === 'slow-http'
                        ? $snortSummary['matches_slow_http_pattern']
                        : false,
                    'parsed_summary' => array_merge(
                        $snortSummary['parsed_summary'] ?? [],
                        ['severity_count' => $snortSummary['severity_count']],
                    ),
                ],
            );

            SnortAlert::where('validation_file_id', $validation->id)->delete();
            $alertsToInsert = [];
            foreach (array_slice($snortSummary['alerts'], 0, 1000) as $alert) {
                $alertsToInsert[] = [
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
            if ($alertsToInsert) {
                SnortAlert::insert($alertsToInsert);
            }
        }

        $experiment->update(['status' => $validation ? 'validated' : 'data_acquired']);
        $imported[] = "{$experiment->experiment_code} {$scenario['key']}: acq={$acquisition->original_name}, val=" . ($validation?->original_name ?? '-');
    }

    foreach ($imported as $line) {
        $this->line($line);
    }
    $this->info('Import selesai.');
    return self::SUCCESS;
})->purpose('Import existing files from storage/app/vm-lab-captures as real experiments');

Artisan::command('acquisition:reparse {id? : Acquisition file id}', function (AcquisitionParser $parser) {
    $query = AcquisitionFile::query();
    if ($this->argument('id')) {
        $query->whereKey($this->argument('id'));
    }

    $count = 0;
    foreach ($query->cursor() as $file) {
        $path = Storage::disk('local')->path($file->stored_name);
        if (!is_file($path)) {
            $this->warn("Skip {$file->id}: file tidak ditemukan {$file->stored_name}");
            continue;
        }

        $summary = $parser->parse($path, $file->extension);
        $file->update([
            'total_packets'    => $summary['total_packets'],
            'tcp_packets'      => $summary['tcp_packets'],
            'http_packets'     => $summary['http_packets'],
            'avg_packet_size'  => $summary['avg_packet_size'],
            'top_source_ips'   => $summary['top_source_ips'],
            'top_destination_ips' => $summary['top_destination_ips'],
            'protocol_distribution' => $summary['protocol_distribution'],
            'total_connections' => $summary['total_connections'],
            'avg_connection_duration' => $summary['avg_connection_duration'],
            'half_open_connections' => $summary['half_open_connections'],
            'parsed_summary'   => $summary['parsed_summary'],
        ]);
        $count++;
        $this->line("Reparsed {$file->id}: {$file->original_name}");
    }

    $this->info("Selesai reparse: {$count} file.");
})->purpose('Re-parse uploaded acquisition files with the current parser');

if (!function_exists('nextExperimentCode')) {
    function nextExperimentCode(): string
    {
        $max = Experiment::query()
            ->pluck('experiment_code')
            ->map(function (?string $code): int {
                if (!$code || !preg_match('/^EXP-(\d+)$/', $code, $matches)) {
                    return 0;
                }

                return (int) $matches[1];
            })
            ->max() ?? 0;

        return 'EXP-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
