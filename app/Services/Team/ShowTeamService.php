<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\InvitationStatus;
use App\Enums\TeamMemberStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ShowTeamService
{
    public function execute(User $actor, Team $team): Team
    {
        if (! $actor->can('view', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return $team->load([
            'competition',
            'captain',
            'coach',
            'members' => fn ($query) => $query
                ->where('status', TeamMemberStatus::Active)
                ->with('user'),
            'invitations' => fn ($query) => $query
                ->where('status', InvitationStatus::Pending)
                ->with(['invitedUser', 'invitedBy']),
        ]);
    }
}
