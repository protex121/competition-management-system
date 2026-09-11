<?php

declare(strict_types=1);

use App\Http\Controllers\Judging\CompetitionJudgeController;
use App\Http\Controllers\Judging\RubricCriterionController;
use App\Http\Controllers\Judging\ScoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('judging/queue', [ScoreController::class, 'queue'])->name('judging.queue.index');
    Route::get('submissions/{submission}/score', [ScoreController::class, 'edit'])->name('submissions.score.edit');
    Route::put('submissions/{submission}/score', [ScoreController::class, 'update'])->name('submissions.score.update');
});

Route::middleware(['auth', 'verified', 'active', 'organizer'])->group(function () {
    Route::post('competitions/{competition}/judges', [CompetitionJudgeController::class, 'store'])
        ->name('competitions.judges.store');
    Route::delete('competitions/{competition}/judges/{judge}', [CompetitionJudgeController::class, 'destroy'])
        ->name('competitions.judges.destroy');

    Route::post('competitions/{competition}/rubric-criteria', [RubricCriterionController::class, 'store'])
        ->name('competitions.rubric-criteria.store');
    Route::put('rubric-criteria/{criterion}', [RubricCriterionController::class, 'update'])
        ->name('rubric-criteria.update');
    Route::delete('rubric-criteria/{criterion}', [RubricCriterionController::class, 'destroy'])
        ->name('rubric-criteria.destroy');
});
