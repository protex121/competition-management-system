<?php

declare(strict_types=1);

use App\Http\Controllers\Competition\PublicCompetitionController;
use Illuminate\Support\Facades\Route;

Route::get('events/{organization}/{competition}', [PublicCompetitionController::class, 'show'])
    ->name('events.competitions.show');
