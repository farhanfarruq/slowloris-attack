<?php

namespace Tests\Unit;

use App\Models\AiResult;
use App\Models\Experiment;
use App\Models\ExtractedFeature;
use App\Services\AnalysisComparisonService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AnalysisComparisonServiceTest extends TestCase
{
    public function test_summary_marks_blocked_ai_detected_when_logic_gate_has_reasons(): void
    {
        $experiment = new Experiment();
        $experiment->forceFill([
            'tool_profile' => 'loic',
            'attack_pattern' => 'http_flood',
            'scenario_key' => 'http-flood-lab',
        ]);

        $features = new ExtractedFeature();
        $features->forceFill([
            'attack_category' => 'Suspicious',
            'final_attack_score' => 62.5,
            'raw_features' => ['gate_reasons' => ['False-positive guard aktif.']],
        ]);
        $experiment->setRelation('extractedFeature', $features);
        $experiment->setRelation('aiResults', new Collection());

        $ai = new AiResult();
        $ai->forceFill([
            'tool_profile' => 'loic',
            'classification' => 'LOIC Detected',
            'confidence_score' => 91,
            'missing_evidence' => [],
            'logic_gate_reasons' => ['False-positive guard aktif.'],
        ]);

        $summary = (new AnalysisComparisonService())->summarize($experiment, $ai);

        $this->assertSame('blocked_by_evidence_gate', $summary['agreement']);
        $this->assertSame('LOIC Detected', $summary['ai_classification']);
        $this->assertSame(62.5, $summary['logic_score']);
    }
}
