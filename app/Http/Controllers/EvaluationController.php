<?php

namespace App\Http\Controllers;

use App\Services\EvaluationMetricsService;

/**
 * Halaman evaluasi: confusion matrix global dan per tool profile.
 * Evaluasi ini mengukur keputusan final program terhadap ground truth lab.
 */
class EvaluationController extends Controller
{
    public function index(EvaluationMetricsService $evaluation)
    {
        return view('evaluation.index', $evaluation->summary());
    }
}
