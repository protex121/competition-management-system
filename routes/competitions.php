<?php

declare(strict_types=1);

use App\Http\Controllers\Competition\CompetitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'organizer'])->group(function () {
    Route::patch('competitions/{competition}/publish', [CompetitionController::class, 'publish'])->name('competitions.publish');
    Route::patch('competitions/{competition}/activate', [CompetitionController::class, 'activate'])->name('competitions.activate');
    Route::patch('competitions/{competition}/close', [CompetitionController::class, 'close'])->name('competitions.close');
    Route::resource('competitions', CompetitionController::class)->except(['show']);
});
