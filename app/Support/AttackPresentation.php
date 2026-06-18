<?php

namespace App\Support;

final class AttackPresentation
{
    public static function scoreLabel(?string $category): string
    {
        $category = trim((string) $category);

        if ($category === '') {
            return '-';
        }

        if (in_array($category, ['Normal', 'Suspicious', 'Inconclusive'], true)) {
            return $category;
        }

        if (self::isDetected($category)) {
            return 'Attack Detected';
        }

        if (str_starts_with($category, 'Possible ')) {
            return 'Possible Attack';
        }

        return $category;
    }

    public static function scoreTone(?string $category): string
    {
        return match (self::scoreLabel($category)) {
            'Normal' => 'score-tone-normal',
            'Suspicious' => 'score-tone-suspicious',
            'Possible Attack' => 'score-tone-possible',
            'Attack Detected' => 'score-tone-strong',
            default => 'score-tone-neutral',
        };
    }

    public static function classificationLabel(?string $classification): string
    {
        $classification = trim((string) $classification);

        if ($classification === '') {
            return '-';
        }

        return self::isDetected($classification) ? 'Attack Detected' : $classification;
    }

    public static function classificationColor(?string $classification): string
    {
        return match (self::classificationLabel($classification)) {
            'Attack Detected' => 'rose',
            'Suspicious' => 'amber',
            'Normal' => 'emerald',
            'Inconclusive' => 'slate',
            default => 'slate',
        };
    }

    public static function decisionLabel(?string $decision): string
    {
        $decision = trim((string) $decision);

        if ($decision === '') {
            return '-';
        }

        if ($decision === 'Serangan asli') {
            return 'Attack Detected';
        }

        if (str_starts_with($decision, 'Indikasi ')) {
            return 'Possible Attack, butuh validasi lanjutan';
        }

        return $decision;
    }

    private static function isDetected(string $label): bool
    {
        return $label === 'Attack Detected'
            || str_ends_with($label, ' Detected')
            || (str_starts_with($label, 'Strong ') && str_ends_with($label, ' Indication'));
    }
}
