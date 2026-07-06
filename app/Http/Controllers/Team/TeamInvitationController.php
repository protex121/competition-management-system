<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\SendTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Services\Team\AcceptTeamInvitationService;
use App\Services\Team\DeclineTeamInvitationService;
use App\Services\Team\ListPendingInvitationsService;
use App\Services\Team\RevokeTeamInvitationService;
use App\Services\Team\SendTeamInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController extends Controller
{
    public function index(Request $request, ListPendingInvitationsService $service): Response
    {
        $invitations = $service->execute($request->user());

        return Inertia::render('team/invitations/Index', [
            'invitations' => $invitations->through(fn (TeamInvitation $invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'expires_at' => $invitation->expires_at->toISOString(),
                'team' => [
                    'id' => $invitation->team->id,
                    'name' => $invitation->team->name,
                ],
                'competition' => [
                    'id' => $invitation->team->competition->id,
                    'name' => $invitation->team->competition->name,
                ],
                'invited_by' => $invitation->invitedBy ? [
                    'name' => $invitation->invitedBy->name,
                ] : null,
            ]),
        ]);
    }

    public function store(
        SendTeamInvitationRequest $request,
        Team $team,
        SendTeamInvitationService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $team, $request->validated());

        return to_route('teams.show', $team);
    }

    public function destroy(
        Request $request,
        Team $team,
        TeamInvitation $invitation,
        RevokeTeamInvitationService $service,
    ): RedirectResponse {
        abort_unless($invitation->team_id === $team->id, 404);

        $service->execute($request->user(), $invitation);

        return to_route('teams.show', $team);
    }

    public function accept(
        Request $request,
        TeamInvitation $invitation,
        AcceptTeamInvitationService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $invitation);

        return to_route('teams.show', $invitation->team_id);
    }

    public function decline(
        Request $request,
        TeamInvitation $invitation,
        DeclineTeamInvitationService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $invitation);

        return to_route('invitations.index');
    }
}
