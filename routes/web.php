<?php

use App\Http\Controllers\AcquisitionController;
use App\Http\Controllers\AiValidationController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ApiSettingController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExperimentController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\MethodologyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewerNoteController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\VisualizationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Eksperimen
    Route::post('/experiments/vm-drafts', [ExperimentController::class, 'createVmDrafts'])
        ->middleware('role:admin')->name('experiments.vm-drafts');
    Route::resource('experiments', ExperimentController::class);

    // Akuisisi
    Route::get('/acquisition', [AcquisitionController::class, 'index'])->name('acquisition.index');
    Route::post('/acquisition', [AcquisitionController::class, 'store'])
        ->middleware('role:admin')->name('acquisition.store');
    Route::delete('/acquisition/{acquisition}', [AcquisitionController::class, 'destroy'])
        ->middleware('role:admin')->name('acquisition.destroy');

    // Validasi
    Route::get('/validation', [ValidationController::class, 'index'])->name('validation.index');
    Route::post('/validation', [ValidationController::class, 'store'])
        ->middleware('role:admin')->name('validation.store');
    Route::delete('/validation/{validation}', [ValidationController::class, 'destroy'])
        ->middleware('role:admin')->name('validation.destroy');

    // Analisis
    Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis.index');
    Route::post('/analysis/{experiment}/process', [AnalysisController::class, 'process'])
        ->middleware('role:admin')->name('analysis.process');
    Route::post('/analysis/{experiment}/correlate', [AnalysisController::class, 'correlate'])
        ->middleware('role:admin')->name('analysis.correlate');
    Route::post('/analysis/{experiment}/report', [AnalysisController::class, 'generateReport'])
        ->middleware('role:admin')->name('analysis.report');

    // Visualisasi
    Route::get('/visualization', [VisualizationController::class, 'index'])->name('visualization.index');
    Route::get('/visualization/{experiment}', [VisualizationController::class, 'index'])->name('visualization.show');

    // AI Analysis
    Route::get('/ai', [AiValidationController::class, 'index'])->name('ai.index');
    Route::get('/ai/{experiment}', [AiValidationController::class, 'show'])->name('ai.show');
    Route::post('/ai/{experiment}/run', [AiValidationController::class, 'run'])
        ->middleware('role:admin')->name('ai.run');
    Route::get('/ai/{experiment}/export', [AiValidationController::class, 'exportJson'])->name('ai.export');

    // Comparison
    Route::get('/comparison', [ComparisonController::class, 'index'])->name('comparison.index');
    Route::get('/comparison/{experiment}', [ComparisonController::class, 'show'])->name('comparison.show');

    // Laboratorium & Metodologi
    Route::get('/lab', [LabController::class, 'index'])->name('lab.index');
    Route::get('/methodology', [MethodologyController::class, 'index'])->name('methodology.index');

    // Laporan
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create/{experiment}', [ReportController::class, 'create'])
        ->middleware('role:admin')->name('reports.create');
    Route::post('/reports/{experiment}', [ReportController::class, 'store'])
        ->middleware('role:admin')->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])
        ->middleware('role:admin')->name('reports.destroy');
    Route::get('/reports/{report}/pdf', [ReportController::class, 'downloadPdf'])->name('reports.pdf');
    Route::get('/experiments/{experiment}/features.csv', [ReportController::class, 'exportFeaturesCsv'])
        ->name('experiments.features.csv');

    // Reviewer notes
    Route::post('/experiments/{experiment}/notes', [ReviewerNoteController::class, 'store'])
        ->name('reviewer-notes.store');
    Route::delete('/reviewer-notes/{note}', [ReviewerNoteController::class, 'destroy'])
        ->name('reviewer-notes.destroy');

    // API setting
    Route::get('/settings/api', [ApiSettingController::class, 'index'])
        ->middleware('role:admin')->name('settings.api');
    Route::post('/settings/api', [ApiSettingController::class, 'store'])
        ->middleware('role:admin')->name('settings.api.store');
    Route::delete('/settings/api/{providerKey}', [ApiSettingController::class, 'destroy'])
        ->middleware('role:admin')->name('settings.api.destroy');

    // Evaluasi akurasi
    Route::get('/evaluation', [\App\Http\Controllers\EvaluationController::class, 'index'])
        ->name('evaluation.index');

    // Audit log (admin only)
    Route::get('/audit-log', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('role:admin')->name('audit.index');
});

require __DIR__.'/auth.php';
