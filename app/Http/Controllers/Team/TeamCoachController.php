<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AssignCoachRequest;
use App\Models\Team;
use App\Services\Team\AssignCoachService;
use App\Services\Team\RemoveCoachService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamCoachController extends Controller
{
    public function store(
        AssignCoachRequest $request,
        Team $team,
        AssignCoachService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $team, $request->validated());

        return to_route('teams.show', $team);
    }

    public function destroy(Request $request, Team $team, RemoveCoachService $service): RedirectResponse
    {
        $service->execute($request->user(), $team);

        return to_route('teams.show', $team);
    }
}
