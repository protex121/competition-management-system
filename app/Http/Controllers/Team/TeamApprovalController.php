<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\RejectTeamRequest;
use App\Models\Competition;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\Team\ApproveTeamService;
use App\Services\Team\ListPendingTeamsForReviewService;
use App\Services\Team\RejectTeamService;
use App\Services\Team\SubmitTeamForApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamApprovalController extends Controller
{
    public function review(
        Request $request,
        Competition $competition,
        ListPendingTeamsForReviewService $service,
    ): Response {
        $user = $request->user();
        $teams = $service->execute($user, $competition);

        return Inertia::render('team/teams/Review', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
            'teams' => $teams->through(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'submitted_at' => $team->submitted_at?->toISOString(),
                'captain' => $team->captain ? [
                    'name' => $team->captain->name,
                ] : null,
                'members' => $team->members->map(fn (TeamMember $member) => [
                    'id' => $member->id,
                    'user' => ['name' => $member->user->name],
                ]),
                'can' => [
                    'approve' => $user->can('approve', $team),
                    'reject' => $user->can('reject', $team),
                ],
            ]),
        ]);
    }

    public function submit(
        Request $request,
        Team $team,
        SubmitTeamForApprovalService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $team);

        return to_route('teams.show', $team);
    }

    public function approve(
        Request $request,
        Team $team,
        ApproveTeamService $service,
    ): RedirectResponse {
        $team = $service->execute($request->user(), $team);

        return to_route('competitions.teams.review', $team->competition_id);
    }

    public function reject(
        RejectTeamRequest $request,
        Team $team,
        RejectTeamService $service,
    ): RedirectResponse {
        $team = $service->execute($request->user(), $team, $request->validated());

        return to_route('competitions.teams.review', $team->competition_id);
    }
}
