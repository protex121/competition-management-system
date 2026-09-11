<?php

declare(strict_types=1);

use App\Http\Controllers\Leaderboard\PublicLeaderboardController;
use Illuminate\Support\Facades\Route;

Route::get('events/{organization}/{competition}/leaderboard', [PublicLeaderboardController::class, 'show'])
    ->name('events.competitions.leaderboard');
