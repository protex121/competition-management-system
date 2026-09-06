<?php

declare(strict_types=1);

use App\Http\Controllers\Judging\CompetitionJudgeController;
use App\Http\Controllers\Judging\RubricCriterionController;
use Illuminate\Support\Facades\Route;

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
