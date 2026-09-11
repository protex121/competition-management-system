<?php

declare(strict_types=1);

namespace App\Listeners\Leaderboard;

use App\Events\Competition\CompetitionClosed;
use App\Jobs\Leaderboard\CalculateLeaderboardJob;

class DispatchLeaderboardCalculation
{
    public function handle(CompetitionClosed $event): void
    {
        CalculateLeaderboardJob::dispatch($event->competition);
    }
}
