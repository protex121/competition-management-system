<?php

declare(strict_types=1);

use App\Http\Controllers\Team\TeamController;
use App\Http\Controllers\Team\TeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('invitations', [TeamInvitationController::class, 'index'])->name('invitations.index');
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('invitations/{invitation}/decline', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::get('competitions/{competition}/teams', [TeamController::class, 'index'])
        ->name('competitions.teams.index');
    Route::get('competitions/{competition}/teams/create', [TeamController::class, 'create'])
        ->name('competitions.teams.create');
    Route::post('competitions/{competition}/teams', [TeamController::class, 'store'])
        ->name('competitions.teams.store');

    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::put('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

    Route::post('teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('teams.invitations.store');
    Route::delete('teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('teams.invitations.destroy');
});
