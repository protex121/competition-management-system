<?php

declare(strict_types=1);

namespace App\Http\Controllers\Competition;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\Competition\ListParticipantCompetitionsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantCompetitionController extends Controller
{
    public function index(Request $request, ListParticipantCompetitionsService $service): Response
    {
        $user = $request->user();

        abort_unless($user !== null && $user->role === UserRole::Participant, 403);

        return Inertia::render('participant/competitions/Index', [
            'competitions' => $service->execute($user),
        ]);
    }
}
