<?php

declare(strict_types=1);

use App\Http\Controllers\Identity\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'organizer'])->group(function () {
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    Route::resource('users', UserController::class)->except(['show']);
});
