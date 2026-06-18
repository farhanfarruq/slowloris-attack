<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\ExtractedFeature;
use App\Models\SnortAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menjalankan korelasi akuisisi + validasi lalu menyimpan ExtractedFeature.
 */
class AnalysisService
{
    public function __construct(
        private ScoringService $scoring,
        private ?ToolProfileService $toolProfiles = null,
    ) {
        $this->toolProfiles ??= new ToolProfileService();
    }

    public function analyze(Experiment $experiment): ExtractedFeature
    {
        $features = $this->scoring->buildFeatures($experiment);
        $toolProfile = $this->toolProfiles->normalize($experiment->tool_profile ?? null);
        $radar    = $this->scoring->computeRadarScores($features, $toolProfile);

        $acquisition = $this->scoring->selectAcquisition($experiment);
        $validation  = $this->scoring->selectValidation($experiment, $acquisition);

        // Korelasi waktu antar Wireshark dan Snort: tetap bonus kecil pada snort score
        // untuk alert yang tersebar di banyak menit, tapi tidak boleh menggantikan
        // gate evidence di evaluateExperiment().
        $correlationBonus = $this->timeCorrelationBonus($experiment);
        $radar['snort_alert_score'] = $this->cap($radar['snort_alert_score'] + $correlationBonus);

        // Evaluasi penuh: skor + gating evidence + skenario-aware.
        $evaluation = $this->scoring->evaluateExperiment($experiment, $features, $radar, $toolProfile);

        $extracted = ExtractedFeature::updateOrCreate(
            ['experiment_id' => $experiment->id],
            $this->filterFeatureColumns(array_merge(
                $features,
                $radar,
                [
                    'final_attack_score' => $evaluation['final_attack_score'],
                    'attack_category'    => $evaluation['attack_category'],
                ],
                [
                    'raw_features' => $features + [
                        'tool_profile'           => $toolProfile,
                        'attack_pattern'         => $experiment->attack_pattern,
                        'analysis_profile_key'   => $evaluation['analysis_profile_key'],
                        'logic_classification'   => $evaluation['logic_classification'],
                        'logic_score'            => $evaluation['logic_score'],
                        'radar_scores'           => $radar,
                        'time_correlation_bonus' => $correlationBonus,
                        'acquisition_file_id'    => $acquisition?->id,
                        'acquisition_file'       => $acquisition?->original_name,
                        'validation_file_id'     => $validation?->id,
                        'validation_file'        => $validation?->original_name,
                        'capture_label'          => $acquisition?->capture_label,
                        'scenario_key'           => $experiment->scenario_key
                            ?? $acquisition?->scenario_key,
                        'raw_attack_category'    => $evaluation['raw_attack_category'],
                        'evidence_gates'         => $evaluation['evidence_gates'],
                        'gate_reasons'           => $evaluation['gate_reasons'],
                        'final_decision'         => $evaluation['final_decision'],
                    ],
                ]
            ))
        );

        $experiment->update([
            'status'            => 'analyzed',
            'experiment_status' => $evaluation['experiment_status'],
        ]);

        return $extracted;
    }

    /**
     * Hanya kolom yang benar-benar ada di tabel extracted_features.
     */
    private function filterFeatureColumns(array $payload): array
    {
        $allowed = [
            'total_packets', 'tcp_packets', 'http_packets', 'avg_packet_size',
            'duration_seconds', 'total_connections', 'long_lived_connections',
            'avg_connection_duration', 'connections_to_http_port', 'throughput_kbps',
            'total_alerts', 'high_severity_alerts', 'medium_severity_alerts',
            'baseline_avg_connections', 'baseline_throughput_kbps', 'baseline_alert_count',
            'connection_duration_score', 'header_anomaly_score',
            'low_bandwidth_high_connection_score', 'snort_alert_score',
            'tcp_connection_score', 'baseline_deviation_score', 'ai_confidence_score',
            'final_attack_score', 'attack_category', 'raw_features',
        ];
        return array_intersect_key($payload, array_flip($allowed));
    }

    /**
     * Bonus kecil (max 5) jika alert Snort tersebar di banyak menit:
     * mengindikasikan serangan yang persistent, bukan satu spike.
     */
    private function timeCorrelationBonus(Experiment $experiment): float
    {
        $acquisition = $this->scoring->selectAcquisition($experiment);
        $validation  = $this->scoring->selectValidation($experiment, $acquisition);

        $query = SnortAlert::where('experiment_id', $experiment->id)
            ->whereNotNull('alert_timestamp');

        if ($validation) {
            $query->where('validation_file_id', $validation->id);
        }

        $count = $query->select(DB::raw("strftime('%Y-%m-%d %H:%M', alert_timestamp) as bucket"))
            ->distinct()
            ->count();

        // Hanya beri bonus jika bucket >= 2 (artinya alert tersebar lintas menit).
        if ($count < 2) {
            return 0.0;
        }

        return (float) min(5, ($count - 1) * 1.0);
    }

    private function cap(float $v): float
    {
        return max(0, min(100, $v));
    }

    /**
     * Bangun JSON ringkas yang dikirim ke AI.
     */
    public function buildAiPayload(Experiment $experiment): array
    {
        $features = $experiment->extractedFeature;
        $toolProfile = $this->toolProfiles->normalize($experiment->tool_profile ?? null);
        $radar = $features
            ? $features->radarScores()
            : ($this->scoring->computeRadarScores($this->scoring->buildFeatures($experiment), $toolProfile));

        // Hilangkan ai_confidence_score karena belum dihitung sebelum kirim ke AI
        $radarForAi = $radar;
        unset($radarForAi['ai_confidence_score']);

        $packetSummary = [
            'total_packets'   => (int) ($features?->total_packets ?? 0),
            'tcp_packets'     => (int) ($features?->tcp_packets ?? 0),
            'http_packets'    => (int) ($features?->http_packets ?? 0),
            'avg_packet_size' => (float) ($features?->avg_packet_size ?? 0),
            'duration_seconds'=> (int) ($features?->duration_seconds ?? 0),
        ];

        $connectionSummary = [
            'total_connections'             => (int) ($features?->total_connections ?? 0),
            'long_lived_connections'        => (int) ($features?->long_lived_connections ?? 0),
            'avg_connection_duration_seconds'=> (float) ($features?->avg_connection_duration ?? 0),
            'connections_to_http_port'      => (int) ($features?->connections_to_http_port ?? 0),
            'throughput_kbps'               => (float) ($features?->throughput_kbps ?? 0),
        ];

        $acq = $this->scoring->selectAcquisition($experiment);
        $val = $this->scoring->selectValidation($experiment, $acq);
        $sev = is_array($val?->parsed_summary)
            ? ($val->parsed_summary['severity_count'] ?? [])
            : [];

        $snortAlertSummary = [
            'total_alerts'           => (int) ($val?->total_alerts ?? 0),
            'high_severity_alerts'   => (int) ($sev['high'] ?? 0),
            'medium_severity_alerts' => (int) ($sev['medium'] ?? 0),
            'low_severity_alerts'    => (int) ($sev['low'] ?? 0),
            'dominant_alert_type'    => $val?->dominant_alert_type,
        ];

        $baselineSummary = [
            'normal_avg_connections' => $features?->baseline_avg_connections ?? ScoringService::BASELINE_DEFAULT_CONNECTIONS,
            'normal_throughput_kbps' => $features?->baseline_throughput_kbps ?? ScoringService::BASELINE_DEFAULT_THROUGHPUT,
            'normal_alert_count'     => $features?->baseline_alert_count ?? ScoringService::BASELINE_DEFAULT_ALERTS,
        ];

        $evidenceContract = $this->buildAiEvidenceContract(
            $experiment,
            $packetSummary,
            $connectionSummary,
            $snortAlertSummary,
            $radarForAi,
            is_array($features?->raw_features) ? $features->raw_features : [],
        );
        $logicAnalysis = [
            'classification' => $features?->attack_category ?? 'Inconclusive',
            'score' => (float) ($features?->final_attack_score ?? 0),
            'gate_reasons' => is_array($features?->raw_features)
                ? ($features->raw_features['gate_reasons'] ?? [])
                : [],
            'evidence_gates' => is_array($features?->raw_features)
                ? ($features->raw_features['evidence_gates'] ?? [])
                : [],
        ];

        return [
            'experiment_id'         => $experiment->experiment_code,
            'experiment_name'       => $experiment->name,
            'tool_profile'          => $toolProfile,
            'tool_label'            => $this->toolProfiles->get($toolProfile)['label'] ?? $toolProfile,
            'attack_pattern'        => $experiment->attack_pattern,
            'analysis_profile_key'  => $experiment->analysis_profile_key ?? $toolProfile,
            'target_platform'       => $experiment->target_platform,
            'scenario_key'          => $experiment->scenario_key,
            'traffic_type'          => $experiment->traffic_type,
            'paired_files'          => [
                'acquisition_file_id' => $acq?->id,
                'acquisition_file'    => $acq?->original_name,
                'validation_file_id'  => $val?->id,
                'validation_file'     => $val?->original_name,
                'capture_label'       => $acq?->capture_label,
            ],
            'packet_summary'        => $packetSummary,
            'connection_summary'    => $connectionSummary,
            'snort_alert_summary'   => $snortAlertSummary,
            'baseline_summary'      => $baselineSummary,
            'extracted_features'    => $features ? array_intersect_key(
                $features->toArray(),
                array_flip([
                    'total_packets','tcp_packets','http_packets','total_connections',
                    'avg_connection_duration','throughput_kbps','total_alerts',
                ])
            ) : [],
            'radar_score'           => $radarForAi,
            'logic_analysis'        => $logicAnalysis,
            'evidence_contract'     => $evidenceContract,
            'suspected_attack_type' => $features?->attack_category ?? 'Unknown',
        ];
    }

    private function buildAiEvidenceContract(
        Experiment $experiment,
        array $packetSummary,
        array $connectionSummary,
        array $snortAlertSummary,
        array $radarForAi,
        array $rawFeatures,
    ): array {
        $totalPackets = max(1, (int) ($packetSummary['total_packets'] ?? 0));
        $httpRatio = ((int) ($packetSummary['http_packets'] ?? 0)) / $totalPackets;
        $toolProfile = $this->toolProfiles->normalize($experiment->tool_profile ?? null);
        $profile = $this->toolProfiles->get($toolProfile);
        $scenarioKey = (string) ($experiment->scenario_key ?? $rawFeatures['scenario_key'] ?? '');
        $dominantAlert = strtolower((string) ($snortAlertSummary['dominant_alert_type'] ?? ''));
        $gateFlags = is_array($rawFeatures['evidence_gates'] ?? null) ? $rawFeatures['evidence_gates'] : [];
        $relevantSnortAlert = ((float) ($radarForAi['snort_alert_score'] ?? 0)) >= 30
            || str_contains($dominantAlert, 'slowloris')
            || str_contains($dominantAlert, 'slow http')
            || str_contains($dominantAlert, 'slow-http')
            || str_contains($dominantAlert, 'dos')
            || str_contains($dominantAlert, 'flood');

        $checks = [
            'tool_profile_matches_payload' => $toolProfile === $this->toolProfiles->normalize($rawFeatures['tool_profile'] ?? $toolProfile),
            'http_traffic_present' => $httpRatio >= 0.10,
            'http_endpoint_traffic_present' => $httpRatio >= 0.10
                || ((int) ($connectionSummary['connections_to_http_port'] ?? 0)) > 0
                || ((float) ($radarForAi['http_volume_score'] ?? 0)) >= 50,
            'long_lived_http_connections' => ((float) ($radarForAi['connection_duration_score'] ?? 0)) >= 60
                && ((int) ($connectionSummary['long_lived_connections'] ?? 0)) >= 20,
            'low_bandwidth_many_connections' => ((float) ($radarForAi['low_bandwidth_high_connection_score'] ?? 0)) >= 60,
            'relevant_snort_alert' => $relevantSnortAlert,
            'blocked_non_slowloris_scenario' => in_array($scenarioKey, ScoringService::NON_SLOWLORIS_SCENARIOS, true),
            'profile_signal_present' => (bool) ($gateFlags['profile_signal_present'] ?? $gateFlags['composite_signal_passed'] ?? false),
            'volume_signal_present' => (bool) ($gateFlags['volume_signal_present'] ?? false),
            'snort_relevant' => (bool) ($gateFlags['snort_relevant'] ?? $relevantSnortAlert),
        ];

        if ($toolProfile === 'slowloris') {
            $combinedEvidenceCount = collect([
                $checks['long_lived_http_connections'],
                $checks['low_bandwidth_many_connections'],
                $checks['relevant_snort_alert'],
            ])->filter()->count();

            $detectionAllowed = $checks['http_traffic_present']
                && !$checks['blocked_non_slowloris_scenario']
                && $combinedEvidenceCount >= 2;
        } else {
            $combinedEvidenceCount = collect([
                $checks['volume_signal_present'],
                $checks['profile_signal_present'],
                $checks['snort_relevant'],
            ])->filter()->count();

            $detectionAllowed = !($gateFlags['scenario_blocks_attack'] ?? false)
                && !($gateFlags['is_portscan'] ?? false)
                && ($gateFlags['composite_signal_passed'] ?? false);
        }

        $detectedLabel = $this->toolProfiles->detectedLabel($toolProfile);
        $detectedAllowedKey = $this->toolProfiles->detectedAllowedKey($toolProfile);

        return [
            'allowed_classifications' => ['Normal', 'Suspicious', $detectedLabel, 'Inconclusive'],
            'tool_profile' => $toolProfile,
            'detected_label' => $detectedLabel,
            'detected_allowed' => $detectionAllowed,
            $detectedAllowedKey => $detectionAllowed,
            'slowloris_detected_allowed' => $detectionAllowed,
            'required_for_detected' => [
                'detected_allowed must be true',
                'profile_signal_present must match ' . ($profile['label'] ?? $toolProfile),
                'false-positive guards must not block the profile',
                in_array($toolProfile, ['loic', 'hoic', 'xerxes'], true)
                    ? 'HTTP flood profiles may use decoded HTTP packets, connections_to_http_port, http_volume_score, or profile_signal_present as HTTP evidence; long-lived HTTP connections are not required.'
                    : 'Use the active profile evidence, not another tool profile evidence.',
            ],
            'required_for_slowloris_detected' => $toolProfile === 'slowloris' ? [
                'http_traffic_present must be true',
                'blocked_non_slowloris_scenario must be false',
                'at least 2 of long_lived_http_connections, low_bandwidth_many_connections, relevant_snort_alert must be true',
            ] : [],
            'checks' => $checks,
            'combined_evidence_count' => $combinedEvidenceCount,
            'gate_reasons' => $rawFeatures['gate_reasons'] ?? [],
            'final_decision' => $rawFeatures['final_decision'] ?? null,
        ];
    }
}
