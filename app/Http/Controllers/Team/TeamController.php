<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Competition;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Services\Team\CreateTeamService;
use App\Services\Team\DeleteTeamService;
use App\Services\Team\ListTeamsService;
use App\Services\Team\ShowTeamService;
use App\Services\Team\UpdateTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request, Competition $competition, ListTeamsService $service): Response
    {
        $this->authorize('view', $competition);

        $user = $request->user();

        return Inertia::render('team/teams/Index', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
                'status' => $competition->status->value,
                'registration_mode' => $competition->registration_mode->value,
                'min_team_size' => $competition->min_team_size,
                'max_team_size' => $competition->max_team_size,
            ],
            'teams' => $service->execute($user, $competition),
            'can' => [
                'create' => $user->can('create', [Team::class, $competition]),
            ],
        ]);
    }

    public function create(Request $request, Competition $competition): Response
    {
        $this->authorize('create', [Team::class, $competition]);

        return Inertia::render('team/teams/Create', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'min_team_size' => $competition->min_team_size,
                'max_team_size' => $competition->max_team_size,
            ],
        ]);
    }

    public function store(
        StoreTeamRequest $request,
        Competition $competition,
        CreateTeamService $service,
    ): RedirectResponse {
        $team = $service->execute($request->user(), $competition, $request->validated());

        return to_route('teams.show', $team);
    }

    public function show(Request $request, Team $team, ShowTeamService $service): Response
    {
        $team = $service->execute($request->user(), $team);
        $user = $request->user();

        return Inertia::render('team/teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'status' => $team->status->value,
                'rejection_reason' => $team->rejection_reason,
                'submitted_at' => $team->submitted_at?->toISOString(),
                'approved_at' => $team->approved_at?->toISOString(),
                'captain' => $team->captain ? [
                    'id' => $team->captain->id,
                    'name' => $team->captain->name,
                ] : null,
                'competition' => [
                    'id' => $team->competition->id,
                    'name' => $team->competition->name,
                ],
                'members' => $team->members->map(fn (TeamMember $member) => [
                    'id' => $member->id,
                    'role' => $member->role->value,
                    'user' => [
                        'id' => $member->user->id,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                    ],
                ]),
                'pending_invitations' => $team->invitations->map(fn (TeamInvitation $invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'expires_at' => $invitation->expires_at->toISOString(),
                    'can' => [
                        'revoke' => $user->can('revoke', $invitation),
                    ],
                ]),
            ],
            'can' => [
                'update' => $user->can('update', $team),
                'delete' => $user->can('delete', $team),
                'manageMembers' => $user->can('manageMembers', $team),
                'invite' => $user->can('invite', $team),
                'submit' => $user->can('submit', $team),
            ],
        ]);
    }

    public function update(
        UpdateTeamRequest $request,
        Team $team,
        UpdateTeamService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $team, $request->validated());

        return to_route('teams.show', $team);
    }

    public function destroy(Request $request, Team $team, DeleteTeamService $service): RedirectResponse
    {
        $competitionId = $team->competition_id;
        $service->execute($request->user(), $team);

        return to_route('competitions.teams.index', $competitionId);
    }
}
