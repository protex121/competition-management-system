<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\Team\LeaveTeamService;
use App\Services\Team\RemoveTeamMemberService;
use App\Services\Team\TransferCaptainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function transferCaptain(
        Request $request,
        Team $team,
        TeamMember $member,
        TransferCaptainService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $team, $member);

        return to_route('teams.show', $team);
    }

    public function destroy(
        Request $request,
        Team $team,
        TeamMember $member,
        RemoveTeamMemberService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $team, $member);

        return to_route('teams.show', $team);
    }

    public function leave(Request $request, Team $team, LeaveTeamService $service): RedirectResponse
    {
        $service->execute($request->user(), $team);

        return to_route('competitions.teams.index', $team->competition_id);
    }
}
