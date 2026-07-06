<?php

declare(strict_types=1);

use App\Http\Controllers\Competition\CategoryController;
use App\Http\Controllers\Competition\CompetitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'organizer'])->group(function () {
    Route::patch('competitions/{competition}/publish', [CompetitionController::class, 'publish'])->name('competitions.publish');
    Route::patch('competitions/{competition}/activate', [CompetitionController::class, 'activate'])->name('competitions.activate');
    Route::patch('competitions/{competition}/close', [CompetitionController::class, 'close'])->name('competitions.close');
    Route::resource('competitions', CompetitionController::class)->except(['show']);

    Route::scopeBindings()->group(function () {
        Route::post('competitions/{competition}/categories', [CategoryController::class, 'store'])->name('competitions.categories.store');
        Route::put('competitions/{competition}/categories/{category}', [CategoryController::class, 'update'])->name('competitions.categories.update');
        Route::delete('competitions/{competition}/categories/{category}', [CategoryController::class, 'destroy'])->name('competitions.categories.destroy');
        Route::patch('competitions/{competition}/categories/{category}/activate', [CategoryController::class, 'activate'])->name('competitions.categories.activate');
        Route::patch('competitions/{competition}/categories/{category}/disable', [CategoryController::class, 'disable'])->name('competitions.categories.disable');
    });
});
