<?php

namespace App\Http\Controllers;

use App\Models\Experiment;

/**
 * Halaman evaluasi akurasi: confusion matrix + precision/recall/F1
 * berdasarkan ground_truth_label vs experiment_status sistem.
 *
 * Mapping ground truth → kelas biner Slowloris:
 *   slowloris_lab, mixed → POSITIF (serangan)
 *   normal               → NEGATIF (bukan serangan)
 *   unknown              → diabaikan (tidak punya label)
 *
 * Mapping experiment_status sistem → prediksi biner:
 *   attack_detected      → POSITIF
 *   normal               → NEGATIF
 *   suspicious           → POSITIF (sistem cenderung curiga)
 *   inconclusive/pending → diabaikan
 */
class EvaluationController extends Controller
{
    public function index()
    {
        $experiments = Experiment::with('extractedFeature')
            ->whereIn('ground_truth_label', ['normal', 'slowloris_lab', 'mixed'])
            ->whereIn('experiment_status', ['attack_detected', 'normal', 'suspicious'])
            ->orderBy('experiment_date', 'desc')
            ->get();

        $tp = 0; $tn = 0; $fp = 0; $fn = 0;
        $rows = [];

        foreach ($experiments as $exp) {
            $actual = in_array($exp->ground_truth_label, ['slowloris_lab', 'mixed'], true);
            $predicted = in_array($exp->experiment_status, ['attack_detected', 'suspicious'], true);

            if ($actual && $predicted)        { $tp++; $type = 'TP'; }
            elseif (!$actual && !$predicted)  { $tn++; $type = 'TN'; }
            elseif (!$actual && $predicted)   { $fp++; $type = 'FP'; }
            else                              { $fn++; $type = 'FN'; }

            $rows[] = [
                'experiment' => $exp,
                'actual'     => $actual ? 'attack' : 'normal',
                'predicted'  => $predicted ? 'attack' : 'normal',
                'final_score'=> $exp->extractedFeature?->final_attack_score,
                'category'   => $exp->extractedFeature?->attack_category,
                'type'       => $type,
            ];
        }

        $total = $tp + $tn + $fp + $fn;
        $accuracy  = $total > 0 ? ($tp + $tn) / $total : 0;
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
        $recall    = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
        $f1        = ($precision + $recall) > 0
                     ? 2 * ($precision * $recall) / ($precision + $recall)
                     : 0;

        $metrics = [
            'tp' => $tp, 'tn' => $tn, 'fp' => $fp, 'fn' => $fn,
            'total'     => $total,
            'accuracy'  => round($accuracy * 100, 2),
            'precision' => round($precision * 100, 2),
            'recall'    => round($recall * 100, 2),
            'f1'        => round($f1 * 100, 2),
        ];

        return view('evaluation.index', compact('rows', 'metrics'));
    }
}
