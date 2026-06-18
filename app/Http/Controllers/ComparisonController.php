<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Services\AnalysisComparisonService;
use App\Services\ToolProfileService;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    public function __construct(
        private AnalysisComparisonService $comparison,
        private ToolProfileService $toolProfiles,
    ) {
    }

    public function index(Request $request)
    {
        $experiments = Experiment::with(['extractedFeature', 'aiResults'])
            ->when($request->get('tool_profile'), fn ($q, $profile) => $q->where('tool_profile', $this->toolProfiles->normalize($profile)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('comparison.index', [
            'experiments' => $experiments,
            'toolProfiles' => $this->toolProfiles->options(),
        ]);
    }

    public function show(Experiment $experiment)
    {
        $experiment->load(['extractedFeature', 'aiResults']);

        return view('comparison.show', [
            'experiment' => $experiment,
            'comparisons' => $this->comparison->forExperiment($experiment),
        ]);
    }
}
