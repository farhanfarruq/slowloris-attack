<?php

namespace App\Services;

class AiPromptBuilder
{
    public function __construct(private ?ToolProfileService $profiles = null)
    {
        $this->profiles ??= new ToolProfileService();
    }

    public function build(array $payload): array
    {
        $toolProfile = $this->profiles->normalize($payload['tool_profile'] ?? null);
        $profile = $this->profiles->get($toolProfile);
        $detectedLabel = $this->profiles->detectedLabel($toolProfile);

        $schema = [
            'tool_profile' => 'string; must equal payload.tool_profile',
            'attack_pattern' => 'string|null; copy from payload.attack_pattern when available',
            'classification' => 'one of: Normal, Suspicious, ' . $detectedLabel . ', Inconclusive',
            'confidence_score' => 'number 0-100; confidence in selected classification',
            'reason' => 'short evidence-based conclusion using only payload values',
            'supporting_indicators' => [
                ['field' => 'payload field path', 'value' => 'observed value', 'interpretation' => 'defensive forensic interpretation'],
            ],
            'missing_evidence' => ['strings naming missing or weak evidence'],
            'false_positive_considerations' => ['strings naming relevant false positive guards'],
            'logic_comparison' => [
                'logic_classification' => 'string',
                'logic_score' => 'number',
                'agreement' => 'match|partial|conflict|blocked_by_evidence_gate',
                'explanation' => 'string',
            ],
            'chart_data' => [
                'indicator_scores' => [['label' => 'string', 'score' => 'number']],
                'evidence_counts' => ['present' => 'number', 'missing' => 'number', 'blocking' => 'number'],
                'confidence' => 'number',
            ],
            'recommendation' => 'defensive validation or monitoring next step',
        ];

        $system = "You are a defensive network forensic analyst for controlled lab DDoS research.\n"
            . "Return JSON only. Do not include markdown.\n"
            . "Analyze only the DDoS tool profile declared in payload.tool_profile: {$toolProfile}.\n"
            . "The tool profile is the research identity. The attack pattern is technical evidence context only.\n"
            . "Do not reuse indicators from another tool profile.\n"
            . "Use only values present in the payload. Do not invent IPs, timestamps, rule names, packet counts, connection counts, ports, target hardware, or model results.\n"
            . "Allowed classification values: Normal, Suspicious, {$detectedLabel}, Inconclusive.\n"
            . "The detected label is allowed only when payload.evidence_contract.detected_allowed is true.\n"
            . "If detected_allowed is false, classification must be Normal, Suspicious, or Inconclusive.\n"
            . "Confidence score means confidence in your selected classification. It is not attack probability unless classification equals {$detectedLabel}.\n"
            . "Use payload.evidence_contract.required_for_detected for this active tool profile. Do not require Slowloris-only long-lived/low-bandwidth evidence for LOIC, HOIC, or Xerxes HTTP flood profiles unless the payload explicitly says slow_http.\n"
            . "Profile-specific prompt rules: " . implode(' ', $profile['prompt_rules'] ?? []) . "\n"
            . "False-positive guards: " . implode(', ', $profile['false_positive_guards'] ?? []) . "\n"
            . "Output schema: " . json_encode($schema, JSON_UNESCAPED_UNICODE);

        $user = "Analyze this defensive lab payload and compare AI analysis with logic scoring.\n\n"
            . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return ['system' => $system, 'user' => $user];
    }
}
