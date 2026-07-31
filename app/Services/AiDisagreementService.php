<?php

namespace App\Services;

use App\Models\Experiment;

class AiDisagreementService
{
    public function summary(): array
    {
        return Experiment::query()
            ->with(['extractedFeature', 'aiResults' => fn ($query) => $query->latest()])
            ->whereHas('aiResults')
            ->get()
            ->map(function (Experiment $experiment) {
                $ai = $experiment->aiResults->first();
                $logic = $this->logicClass($experiment->experiment_status);
                $aiClass = $this->aiClass((string) $ai->classification);

                if ($logic === $aiClass) {
                    return null;
                }

                return [
                    'experiment' => $experiment,
                    'model' => $ai->model_name,
                    'logic' => $logic,
                    'ai' => $aiClass,
                    'classification' => $ai->classification,
                    'confidence' => $ai->confidence_score,
                    'category' => $this->category($logic, $aiClass),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function logicClass(?string $status): string
    {
        return match ($status) {
            'attack_detected' => 'attack',
            'normal' => 'normal',
            'suspicious' => 'suspicious',
            default => 'inconclusive',
        };
    }

    private function aiClass(string $classification): string
    {
        $label = strtolower($classification);

        return match (true) {
            str_contains($label, 'detected') || str_contains($label, 'attack') => 'attack',
            str_contains($label, 'normal') => 'normal',
            str_contains($label, 'suspicious') => 'suspicious',
            default => 'inconclusive',
        };
    }

    private function category(string $logic, string $ai): string
    {
        return match (true) {
            $ai === 'attack' && $logic !== 'attack' => 'AI over-detect',
            $logic === 'attack' && $ai !== 'attack' => 'AI under-detect',
            $ai === 'inconclusive' => 'Inconclusive',
            default => 'Classification mismatch',
        };
    }
}
