<?php

declare(strict_types=1);

use App\Http\Controllers\Team\ParticipantProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->prefix('participant')->name('participant.')->group(function () {
    Route::get('profile', [ParticipantProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ParticipantProfileController::class, 'update'])->name('profile.update');
});
