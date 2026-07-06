<?php

declare(strict_types=1);

use App\Http\Controllers\Team\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('competitions/{competition}/teams', [TeamController::class, 'index'])
        ->name('competitions.teams.index');
    Route::get('competitions/{competition}/teams/create', [TeamController::class, 'create'])
        ->name('competitions.teams.create');
    Route::post('competitions/{competition}/teams', [TeamController::class, 'store'])
        ->name('competitions.teams.store');

    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::put('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
});
