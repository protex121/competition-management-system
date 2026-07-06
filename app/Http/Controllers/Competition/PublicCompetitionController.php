<?php

declare(strict_types=1);

namespace App\Http\Controllers\Competition;

use App\Http\Controllers\Controller;
use App\Services\Competition\ShowPublicCompetitionService;
use Inertia\Inertia;
use Inertia\Response;

class PublicCompetitionController extends Controller
{
    public function show(
        string $organization,
        string $competition,
        ShowPublicCompetitionService $service,
    ): Response {
        $data = $service->execute($organization, $competition);

        return Inertia::render('competition/public/Show', $data);
    }
}
