<?php

declare(strict_types=1);

namespace App\Http\Controllers\Leaderboard;

use App\Http\Controllers\Controller;
use App\Services\Leaderboard\ShowPublicLeaderboardService;
use Inertia\Inertia;
use Inertia\Response;

class PublicLeaderboardController extends Controller
{
    public function show(
        string $organization,
        string $competition,
        ShowPublicLeaderboardService $service,
    ): Response {
        $data = $service->execute($organization, $competition);

        return Inertia::render('leaderboard/Show', $data);
    }
}
