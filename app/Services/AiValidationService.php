<?php

namespace App\Services;

use App\Models\AiProviderSetting;
use App\Models\AiResult;
use App\Models\Experiment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mengirim ringkasan fitur (bukan file mentah) ke berbagai LLM provider.
 * Mendukung provider live: groq, openai, gemini, ollama.
 *
 * API key DILARANG bocor ke frontend.
 */
class AiValidationService
{
    public function __construct(
        private AnalysisService $analysis,
        private ?AiPromptBuilder $promptBuilder = null,
        private ?AnalysisComparisonService $comparison = null,
        private ?ToolProfileService $toolProfiles = null,
    ) {
        $this->toolProfiles ??= new ToolProfileService();
        $this->promptBuilder ??= new AiPromptBuilder($this->toolProfiles);
        $this->comparison ??= new AnalysisComparisonService($this->toolProfiles);
    }

    public function listProviders(): array
    {
        $providers = $this->configuredProviders();
        $out = [];

        foreach ($providers as $key => $cfg) {
            if (!$this->providerCanRun($key, $cfg)) {
                continue;
            }

            $out[] = [
                'key' => $key,
                'label' => $cfg['label'] ?? $key,
                'driver' => $cfg['driver'] ?? 'unknown',
                'has_key' => ($cfg['driver'] ?? null) === 'ollama' ? true : !empty($cfg['api_key']),
                'model' => $cfg['model'] ?? null,
                'api_url' => $cfg['api_url'] ?? null,
                'tool_profile' => $cfg['tool_profile'] ?? null,
                'use_live_api' => (bool) ($cfg['use_live_api'] ?? false),
                'can_run' => $this->providerCanRun($key, $cfg),
            ];
        }

        return $out;
    }

    public function listProviderSettings(): array
    {
        $providers = $this->configuredProviders(includeStoredWithoutCredentials: true);
        $out = [];

        foreach ($providers as $key => $cfg) {
            $out[] = [
                'key' => $key,
                'label' => $cfg['label'] ?? $key,
                'driver' => $cfg['driver'] ?? 'openai_compatible',
                'has_key' => ($cfg['driver'] ?? null) === 'ollama' ? false : !empty($cfg['api_key']),
                'model' => $cfg['model'] ?? null,
                'api_url' => $cfg['api_url'] ?? null,
                'tool_profile' => $cfg['tool_profile'] ?? null,
                'use_live_api' => (bool) ($cfg['use_live_api'] ?? false),
                'can_run' => $this->providerCanRun($key, $cfg),
            ];
        }

        return $out;
    }

    public function runForExperiment(Experiment $experiment, array $providerKeys): array
    {
        $payload = $this->analysis->buildAiPayload($experiment);
        $validationRunId = (string) Str::uuid();
        $results = [];
        $toolProfile = $this->toolProfiles->normalize($payload['tool_profile'] ?? $experiment->tool_profile ?? null);

        $providers = $this->configuredProviders();

        foreach ($providerKeys as $key) {
            $config = $providers[$key] ?? null;
            if (!$config) continue;
            if (!empty($config['tool_profile']) && $this->toolProfiles->normalize($config['tool_profile']) !== $toolProfile) {
                continue;
            }

            try {
                $response = $this->dispatch($key, $config, $payload);
            } catch (\Throwable $e) {
                Log::error("AI provider $key gagal: " . $e->getMessage());
                throw new \RuntimeException(($config['label'] ?? $key) . ' gagal: ' . $e->getMessage(), previous: $e);
            }

            $attributes = [
                'experiment_id'        => $experiment->id,
                'tool_profile'         => $toolProfile,
                'attack_pattern'       => $payload['attack_pattern'] ?? null,
                'analysis_profile_key' => $payload['analysis_profile_key'] ?? $toolProfile,
                'model_name'           => $response['model_name'],
                'model_version'        => $config['model'] ?? null,
                'classification'       => $response['classification'],
                'confidence_score'     => (float) $response['confidence_score'],
                'logic_classification' => $payload['logic_analysis']['classification'] ?? null,
                'logic_score'          => $payload['logic_analysis']['score'] ?? null,
                'logic_gate_reasons'   => $payload['logic_analysis']['gate_reasons'] ?? [],
                'ai_chart_data'        => $response['chart_data'] ?? $this->defaultAiChartData($response),
                'reason'               => $response['reason'] ?? null,
                'supporting_indicators'=> $response['supporting_indicators'] ?? [],
                'missing_evidence'     => $response['missing_evidence'] ?? [],
                'recommendation'       => $response['recommendation'] ?? null,
                'raw_request'          => $payload,
                'raw_response'         => $response,
                'is_simulated'         => (bool) ($response['is_simulated'] ?? false),
            ];

            if (Schema::hasColumn('ai_results', 'validation_run_id')) {
                $attributes['validation_run_id'] = $validationRunId;
            }

            $ai = AiResult::create($attributes);
            $ai->comparison_summary = $this->comparison->summarize($experiment->fresh(), $ai);
            $ai->save();

            $results[] = $ai;
        }

        // Only "Slowloris Detected" confidence may increase the Slowloris score.
        // "Suspicious"/"Normal"/"Inconclusive" confidence means the model is confident in
        // those labels, NOT that the traffic is a Slowloris attack.
        $features = $experiment->extractedFeature;
        if ($features) {
            $features->ai_confidence_score = $this->attackConfidenceAverage($results);
            $radar = $features->radarScores();

            // Hormati evidence gating: AI tidak boleh men-trigger attack_detected sendirian.
            $scoring = new ScoringService($this->toolProfiles);
            $rawFeatures = $this->buildRawFeaturesFromExtracted($features);
            $evaluation = $scoring->evaluateExperiment($experiment, $rawFeatures, $radar, $toolProfile);

            $features->final_attack_score = $evaluation['final_attack_score'];
            $features->attack_category    = $evaluation['attack_category'];
            $features->save();

            $experiment->update([
                'status'            => 'ai_validated',
                'experiment_status' => $evaluation['experiment_status'],
            ]);
        }

        return $results;
    }

    public function latestResults(Experiment $experiment)
    {
        $base = $experiment->aiResults()
            ->where('is_simulated', false);

        if (Schema::hasColumn('ai_results', 'validation_run_id')) {
            $latestRunId = (clone $base)
                ->whereNotNull('validation_run_id')
                ->latest('created_at')
                ->value('validation_run_id');

            if ($latestRunId) {
                return (clone $base)
                    ->where('validation_run_id', $latestRunId)
                    ->latest()
                    ->get();
            }
        }

        return $base
            ->latest()
            ->get()
            ->unique(fn ($result) => $result->model_name . '|' . ($result->model_version ?? ''))
            ->values();
    }

    private function attackConfidenceAverage(array $results): float
    {
        $attackResults = collect($results)
            ->filter(fn ($result) => $this->isAttackClassification((string) $result->classification, $result->tool_profile ?? null));

        return round($attackResults->avg('confidence_score') ?? 0, 2);
    }

    /**
     * Recreate raw features array shape from a persisted ExtractedFeature
     * so that ScoringService::evaluateExperiment() can re-run gate evaluation.
     */
    private function buildRawFeaturesFromExtracted(\App\Models\ExtractedFeature $f): array
    {
        $raw = is_array($f->raw_features) ? $f->raw_features : [];

        return array_merge($raw, [
            'total_packets'             => (float) ($f->total_packets ?? 0),
            'tcp_packets'               => (float) ($f->tcp_packets ?? 0),
            'udp_packets'               => (float) ($raw['udp_packets'] ?? 0),
            'icmp_packets'              => (float) ($raw['icmp_packets'] ?? 0),
            'http_packets'              => (float) ($f->http_packets ?? 0),
            'avg_packet_size'           => (float) ($f->avg_packet_size ?? 0),
            'duration_seconds'          => (float) ($f->duration_seconds ?? 0),
            'total_connections'         => (float) ($f->total_connections ?? 0),
            'long_lived_connections'    => (float) ($f->long_lived_connections ?? 0),
            'avg_connection_duration'   => (float) ($f->avg_connection_duration ?? 0),
            'connections_to_http_port'  => (float) ($f->connections_to_http_port ?? 0),
            'throughput_kbps'           => (float) ($f->throughput_kbps ?? 0),
            'half_open_connections'     => 0.0,
            'total_alerts'              => (float) ($f->total_alerts ?? 0),
            'high_severity_alerts'      => (float) ($f->high_severity_alerts ?? 0),
            'medium_severity_alerts'    => (float) ($f->medium_severity_alerts ?? 0),
            'low_severity_alerts'       => 0.0,
            'baseline_avg_connections'  => (float) ($f->baseline_avg_connections ?? ScoringService::BASELINE_DEFAULT_CONNECTIONS),
            'baseline_throughput_kbps'  => (float) ($f->baseline_throughput_kbps ?? ScoringService::BASELINE_DEFAULT_THROUGHPUT),
            'baseline_alert_count'      => (float) ($f->baseline_alert_count ?? ScoringService::BASELINE_DEFAULT_ALERTS),
        ]);
    }

    private function isAttackClassification(string $classification, ?string $toolProfile = null): bool
    {
        return $this->toolProfiles->isDetectedLabel($classification, $toolProfile);
    }

    private function dispatch(string $key, array $config, array $payload): array
    {
        $driver = $config['driver'] ?? 'unknown';

        if ($driver === 'simulated' || $key === 'simulated') {
            throw new \RuntimeException('Provider simulasi dinonaktifkan. Gunakan API live atau Ollama lokal yang benar-benar berjalan.');
        }

        if (!($config['use_live_api'] ?? false)) {
            throw new \RuntimeException('Live API belum diaktifkan untuk provider ini.');
        }

        if (empty($config['api_key']) && $driver !== 'ollama') {
            throw new \RuntimeException('API key belum diisi.');
        }

        return match ($driver) {
            'openai_compatible' => $this->callOpenAiCompatible($config, $payload),
            'gemini'            => $this->callGemini($config, $payload),
            'ollama'            => $this->callOllama($config, $payload),
            default             => throw new \RuntimeException('Driver provider tidak didukung: ' . $driver),
        };
    }

    public function providerCanRun(string $key, array $config): bool
    {
        $driver = $config['driver'] ?? 'unknown';
        if ($key === 'simulated' || $driver === 'simulated') {
            return false;
        }

        if (!($config['use_live_api'] ?? false)) {
            return false;
        }

        if (empty($config['api_url']) || empty($config['model'])) {
            return false;
        }

        return $driver === 'ollama' || !empty($config['api_key']);
    }

    private function configuredProviders(bool $includeStoredWithoutCredentials = false): array
    {
        $providers = [];

        try {
            if (!Schema::hasTable('ai_provider_settings')) {
                return $providers;
            }

            $settings = AiProviderSetting::query()->orderBy('provider_label')->get();

            foreach ($settings as $setting) {
                $key = $setting->provider_key;
                if (!$key) {
                    continue;
                }

                if (!$includeStoredWithoutCredentials && empty($setting->api_key) && $setting->driver !== 'ollama') {
                    continue;
                }

                $providers[$key] = [
                    'label' => $setting->provider_label ?: Str::headline($key),
                    'driver' => $setting->driver ?: 'openai_compatible',
                    'api_key' => $setting->api_key,
                    'api_url' => $setting->api_url,
                    'model' => $setting->model,
                    'tool_profile' => $setting->tool_profile,
                    'use_live_api' => (bool) $setting->use_live_api,
                ];
            }

            foreach (config('ai.providers', []) as $key => $cfg) {
                if (isset($providers[$key])) {
                    continue;
                }

                if (empty($cfg['api_key'])) {
                    continue;
                }

                $providers[$key] = [
                    'label' => $cfg['label'] ?? Str::headline($key),
                    'driver' => $cfg['driver'] ?? 'openai_compatible',
                    'api_key' => $cfg['api_key'] ?? null,
                    'api_url' => $cfg['api_url'] ?? null,
                    'model' => $cfg['model'] ?? null,
                    'tool_profile' => $cfg['tool_profile'] ?? null,
                    'use_live_api' => false,
                ];
            }

            foreach (AiProviderSetting::query()->get()->keyBy('provider_key') as $key => $setting) {
                if (!isset($providers[$key])) {
                    continue;
                }

                $providers[$key]['label'] = $setting->provider_label ?: ($providers[$key]['label'] ?? Str::headline($key));
                $providers[$key]['driver'] = $setting->driver ?: ($providers[$key]['driver'] ?? 'openai_compatible');
                $providers[$key]['api_key'] = $setting->api_key ?: ($providers[$key]['api_key'] ?? null);
                $providers[$key]['api_url'] = $setting->api_url ?: ($providers[$key]['api_url'] ?? null);
                $providers[$key]['model'] = $setting->model ?: ($providers[$key]['model'] ?? null);
                $providers[$key]['tool_profile'] = $setting->tool_profile ?: ($providers[$key]['tool_profile'] ?? null);
                $providers[$key]['use_live_api'] = (bool) $setting->use_live_api;
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal membaca pengaturan API dari database: ' . $e->getMessage());
        }

        return $providers;
    }

    private function buildPrompt(array $payload): array
    {
        $prompt = $this->promptBuilder->build($payload);
        $prompt['system'] .= "\nKontrak kompatibilitas: jawab HANYA JSON valid. payload.evidence_contract.slowloris_detected_allowed adalah kunci legacy khusus Slowloris; untuk tool profile lain gunakan payload.evidence_contract.detected_allowed dan required_for_detected. Koneksi banyak saja tidak cukup untuk klasifikasi detected. Semua teks naratif pada output harus berbahasa Indonesia.";

        return $prompt;
    }

    private function callOpenAiCompatible(array $config, array $payload): array
    {
        $prompt = $this->buildPrompt($payload);
        $url = $this->openAiCompatibleEndpoint($config['api_url'] ?? null);

        $resp = Http::timeout(45)
            ->withToken($config['api_key'])
            ->post($url, [
                'model' => $config['model'],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt['system']],
                    ['role' => 'user', 'content' => $prompt['user']],
                ],
            ]);

        $resp->throw();
        $content = $resp->json('choices.0.message.content', '{}');
        $parsed = json_decode($content, true);

        return $this->normalizeResponse($parsed, $config['label'], $payload);
    }

    private function callGemini(array $config, array $payload): array
    {
        $prompt = $this->buildPrompt($payload);
        $url = $this->geminiEndpoint($config);

        $resp = Http::timeout(45)->post($url, [
            'systemInstruction' => ['parts' => [['text' => $prompt['system']]]],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt['user']]]],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ]);

        $resp->throw();
        $text = $resp->json('candidates.0.content.parts.0.text', '{}');
        $parsed = json_decode($text, true);
        return $this->normalizeResponse($parsed, $config['label'], $payload);
    }

    private function openAiCompatibleEndpoint(?string $apiUrl): string
    {
        $url = rtrim((string) $apiUrl, '/');
        if ($url === '') {
            throw new \RuntimeException('Endpoint API kosong.');
        }

        if (preg_match('~/chat/completions$~', $url)) {
            return $url;
        }

        return $url . '/chat/completions';
    }

    private function geminiEndpoint(array $config): string
    {
        $apiUrl = rtrim((string) ($config['api_url'] ?? ''), '/');
        $model = trim((string) ($config['model'] ?? ''));
        $apiKey = (string) ($config['api_key'] ?? '');

        if ($apiUrl === '') {
            throw new \RuntimeException('Endpoint API kosong.');
        }
        if ($model === '' && !str_contains($apiUrl, ':generateContent')) {
            throw new \RuntimeException('Model Gemini kosong.');
        }

        $url = str_contains($apiUrl, ':generateContent')
            ? $apiUrl
            : $apiUrl . '/' . $model . ':generateContent';

        return str_contains($url, '?')
            ? $url . '&key=' . urlencode($apiKey)
            : $url . '?key=' . urlencode($apiKey);
    }

    private function callOllama(array $config, array $payload): array
    {
        $prompt = $this->buildPrompt($payload);
        $resp = Http::timeout(120)->post($config['api_url'], [
            'model' => $config['model'],
            'stream' => false,
            'format' => 'json',
            'messages' => [
                ['role' => 'system', 'content' => $prompt['system']],
                ['role' => 'user', 'content' => $prompt['user']],
            ],
        ]);

        $resp->throw();
        $content = $resp->json('message.content', '{}');
        $parsed = json_decode($content, true);
        return $this->normalizeResponse($parsed, $config['label'], $payload);
    }

    private function normalizeResponse(?array $parsed, string $label, array $payload = []): array
    {
        $parsed = is_array($parsed) ? $parsed : [];
        $toolProfile = $this->toolProfiles->normalize($payload['tool_profile'] ?? $payload['evidence_contract']['tool_profile'] ?? null);
        $detectedLabel = $this->toolProfiles->detectedLabel($toolProfile);
        $classification = $this->normalizeClassification($parsed['classification'] ?? null, $toolProfile);
        $confidence = $this->clampConfidence($parsed['confidence_score'] ?? 0);
        $supportingIndicators = $this->cleanIndicatorList($parsed['supporting_indicators'] ?? []);
        $missingEvidence = $this->cleanStringList($parsed['missing_evidence'] ?? []);
        $falsePositiveConsiderations = $this->cleanStringList($parsed['false_positive_considerations'] ?? []);
        $reason = $this->cleanNullableString($parsed['reason'] ?? null);
        $recommendation = $this->cleanNullableString($parsed['recommendation'] ?? null);
        $chartData = is_array($parsed['chart_data'] ?? null) ? $parsed['chart_data'] : [];
        $logicComparison = is_array($parsed['logic_comparison'] ?? null) ? $parsed['logic_comparison'] : [];
        $downgradeReasons = [];

        if ($classification === $detectedLabel) {
            $contract = is_array($payload['evidence_contract'] ?? null) ? $payload['evidence_contract'] : [];

            if (!($contract['detected_allowed'] ?? $contract['slowloris_detected_allowed'] ?? false)) {
                $downgradeReasons[] = 'Evidence contract menolak label ' . $detectedLabel . '.';
                $missingEvidence = array_values(array_unique(array_merge(
                    $missingEvidence,
                    $this->cleanStringList($contract['gate_reasons'] ?? []),
                )));
            }

            if ($supportingIndicators === []) {
                $downgradeReasons[] = 'supporting_indicators kosong untuk label ' . $detectedLabel . '.';
            }
        }

        if ($downgradeReasons !== []) {
            $classification = 'Inconclusive';
            $confidence = min($confidence, 40.0);
            $reason = trim(($reason ? $reason . ' ' : '') . implode(' ', $downgradeReasons));
            $recommendation ??= 'Ulangi validasi dengan bukti Wireshark dan Snort yang lengkap sebelum menyatakan ' . $detectedLabel . '.';
        }

        return [
            'model_name'            => $this->cleanNullableString($parsed['model_name'] ?? null) ?: $label,
            'tool_profile'          => $toolProfile,
            'attack_pattern'        => $this->cleanNullableString($parsed['attack_pattern'] ?? ($payload['attack_pattern'] ?? null)),
            'classification'        => $classification,
            'confidence_score'      => $confidence,
            'reason'                => $reason,
            'supporting_indicators' => $supportingIndicators,
            'missing_evidence'      => $missingEvidence,
            'false_positive_considerations' => $falsePositiveConsiderations,
            'logic_comparison'      => $logicComparison,
            'chart_data'            => $this->normalizeChartData($chartData, $confidence, $supportingIndicators, $missingEvidence),
            'recommendation'        => $recommendation,
            'is_simulated'          => false,
        ];
    }

    private function normalizeClassification(mixed $value, ?string $toolProfile = null): string
    {
        $normalized = strtolower(trim((string) $value));
        $detectedLabel = $this->toolProfiles->detectedLabel($toolProfile);

        if ($normalized === 'slowloris detected' && $this->toolProfiles->normalize($toolProfile) !== 'slowloris') {
            return 'Inconclusive';
        }

        return match ($normalized) {
            'normal' => 'Normal',
            'suspicious' => 'Suspicious',
            'slowloris detected' => 'Slowloris Detected',
            strtolower($detectedLabel) => $detectedLabel,
            'inconclusive' => 'Inconclusive',
            default => 'Inconclusive',
        };
    }

    private function clampConfidence(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(max(0, min(100, (float) $value)), 2);
    }

    private function cleanStringList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];
        $clean = [];

        foreach ($items as $item) {
            if (is_scalar($item)) {
                $text = trim((string) $item);
                if ($text !== '') {
                    $clean[] = $text;
                }
            }
        }

        return array_slice(array_values(array_unique($clean)), 0, 12);
    }

    private function cleanIndicatorList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];
        $clean = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $field = $this->cleanNullableString($item['field'] ?? null);
                $observedValue = $this->cleanNullableString($item['value'] ?? null);
                $interpretation = $this->cleanNullableString($item['interpretation'] ?? null);
                if ($field || $observedValue || $interpretation) {
                    $clean[] = array_filter([
                        'field' => $field,
                        'value' => $observedValue,
                        'interpretation' => $interpretation,
                    ], fn ($v) => $v !== null);
                }
                continue;
            }

            if (is_scalar($item)) {
                $text = trim((string) $item);
                if ($text !== '') {
                    $clean[] = $text;
                }
            }
        }

        return array_slice($clean, 0, 12);
    }

    private function normalizeChartData(array $chartData, float $confidence, array $supportingIndicators, array $missingEvidence): array
    {
        $chartData['confidence'] = $confidence;
        $chartData['evidence_counts'] = $chartData['evidence_counts'] ?? [
            'present' => count($supportingIndicators),
            'missing' => count($missingEvidence),
            'blocking' => 0,
        ];
        $chartData['indicator_scores'] = $chartData['indicator_scores'] ?? collect($supportingIndicators)
            ->take(6)
            ->map(fn ($item, int $idx) => [
                'label' => is_array($item) ? ($item['field'] ?? 'indicator_' . ($idx + 1)) : 'indicator_' . ($idx + 1),
                'score' => max(10, min(100, $confidence - ($idx * 8))),
            ])
            ->values()
            ->all();

        return $chartData;
    }

    private function defaultAiChartData(array $response): array
    {
        return $this->normalizeChartData(
            is_array($response['chart_data'] ?? null) ? $response['chart_data'] : [],
            (float) ($response['confidence_score'] ?? 0),
            is_array($response['supporting_indicators'] ?? null) ? $response['supporting_indicators'] : [],
            is_array($response['missing_evidence'] ?? null) ? $response['missing_evidence'] : [],
        );
    }

    private function cleanNullableString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * Legacy heuristic only. Not used by validation flow.
     */
    public function simulated(array $payload, string $label = 'Simulated Local Heuristic', ?string $extraReason = null): array
    {
        $radar = $payload['radar_score'] ?? [];
        $avg = collect($radar)->avg() ?? 0;
        $toolProfile = $this->toolProfiles->normalize($payload['tool_profile'] ?? $payload['evidence_contract']['tool_profile'] ?? null);
        $profileLabel = $this->toolProfiles->get($toolProfile)['label'] ?? $toolProfile;
        $detectedLabel = $this->toolProfiles->detectedLabel($toolProfile);

        $classification = match (true) {
            $avg >= 70 => $detectedLabel,
            $avg >= 50 => 'Suspicious',
            $avg <= 25 => 'Normal',
            default    => 'Inconclusive',
        };

        $contract = is_array($payload['evidence_contract'] ?? null) ? $payload['evidence_contract'] : [];
        if ($classification === $detectedLabel && !($contract['detected_allowed'] ?? $contract['slowloris_detected_allowed'] ?? false)) {
            $classification = 'Inconclusive';
        }

        $supporting = [];
        if (($payload['connection_summary']['avg_connection_duration_seconds'] ?? 0) > 60) {
            $supporting[] = 'Koneksi HTTP rata-rata panjang (>60 detik)';
        }
        if (($payload['connection_summary']['throughput_kbps'] ?? 9999) < 200
            && ($payload['connection_summary']['total_connections'] ?? 0) > 200) {
            $supporting[] = 'Throughput rendah dengan jumlah koneksi tinggi';
        }
        if (($payload['snort_alert_summary']['total_alerts'] ?? 0)
            > ($payload['baseline_summary']['normal_alert_count'] ?? 5)) {
            $supporting[] = 'Snort alert melonjak di atas baseline';
        }
        if (str_contains(strtolower($payload['snort_alert_summary']['dominant_alert_type'] ?? ''), 'slow')) {
            $supporting[] = 'Alert dominan terkait pola Slow HTTP';
        }

        $missing = [];
        if (($payload['connection_summary']['avg_connection_duration_seconds'] ?? 0) === 0) {
            $missing[] = 'Durasi rata-rata koneksi belum tersedia';
        }
        if (empty($payload['snort_alert_summary']['dominant_alert_type'])) {
            $missing[] = 'Tipe alert Snort dominan belum tersedia';
        }

        $reason = "Skor radar rata-rata $avg untuk profil $profileLabel menunjukkan klasifikasi $classification."
            . ($extraReason ? " ($extraReason)" : '');

        return [
            'model_name'            => $label,
            'tool_profile'          => $toolProfile,
            'attack_pattern'        => $payload['attack_pattern'] ?? null,
            'classification'        => $classification,
            'confidence_score'      => round(min(100, $avg + 5), 2),
            'reason'                => $reason,
            'supporting_indicators' => $supporting,
            'missing_evidence'      => $missing,
            'recommendation'        => 'Ulangi eksperimen dengan baseline normal yang cukup dan korelasikan dengan rule Snort untuk profil ' . $profileLabel . '.',
            'is_simulated'          => true,
        ];
    }

    public function fallback(array $payload, string $reason): array
    {
        return $this->failedResponse('Provider tidak tersedia', $reason);
    }

    private function failedResponse(string $label, string $reason): array
    {
        return [
            'model_name'            => $label,
            'classification'        => 'Inconclusive',
            'confidence_score'      => 0,
            'reason'                => 'Provider tidak menghasilkan klasifikasi live: ' . $reason,
            'supporting_indicators' => [],
            'missing_evidence'      => ['Tidak ada respons model live yang valid.'],
            'recommendation'        => 'Isi API key, aktifkan live API, atau jalankan Ollama lokal sebelum AI Analysis.',
            'is_simulated'          => false,
        ];
    }

    public function vote(Experiment $experiment): array
    {
        $results = $this->latestResults($experiment);
        if ($results->isEmpty()) {
            return [
                'final_decision' => 'Perlu validasi lanjutan',
                'voting_average_confidence' => 0,
                'voting_summary' => [],
            ];
        }

        $tally = [];
        foreach ($results as $r) {
            $tally[$r->classification] = ($tally[$r->classification] ?? 0) + 1;
        }
        arsort($tally);
        $topClassification = array_key_first($tally);

        // Voting AI tidak boleh menjadi keputusan akhir tanpa bukti Wireshark+Snort.
        // experiment_status sudah di-gate di runForExperiment(); di sini kita hanya
        // melaporkan apa yang model katakan.
        $finalDecision = match (true) {
            $this->isAttackClassification((string) $topClassification, $experiment->tool_profile ?? null) => 'Voting AI: Attack Detected (perlu konfirmasi Wireshark + Snort)',
            $topClassification === 'Normal' => 'Voting AI: Traffic normal',
            $topClassification === 'Suspicious' => 'Voting AI: Suspicious, perlu validasi lanjutan',
            default => 'Voting AI: Inconclusive',
        };

        return [
            'final_decision'            => $finalDecision,
            'voting_average_confidence' => round($results->avg('confidence_score') ?? 0, 2),
            'voting_summary'            => [
                'tally'           => $tally,
                'top_classification' => $topClassification,
                'models'          => $results->map(fn ($r) => [
                    'model'      => $r->model_name,
                    'class'      => $r->classification,
                    'confidence' => $r->confidence_score,
                ])->all(),
            ],
        ];
    }
}
