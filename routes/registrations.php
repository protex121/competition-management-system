<?php

declare(strict_types=1);

use App\Http\Controllers\Registration\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::patch('registrations/{registration}/withdraw', [RegistrationController::class, 'withdraw'])
        ->name('registrations.withdraw');

    Route::post('competitions/{competition}/registrations', [RegistrationController::class, 'storeIndividual'])
        ->name('competitions.registrations.store');
    Route::post('teams/{team}/registrations', [RegistrationController::class, 'storeTeam'])
        ->name('teams.registrations.store');
});

Route::middleware(['auth', 'verified', 'active', 'organizer'])->group(function () {
    Route::scopeBindings()->group(function () {
        Route::get('competitions/{competition}/categories/{category}/registrations', [RegistrationController::class, 'review'])
            ->name('competitions.categories.registrations.index');
    });
});
