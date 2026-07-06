<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\InvitationStatus;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeclineTeamInvitationService
{
    public function execute(User $actor, TeamInvitation $invitation): TeamInvitation
    {
        if (! $actor->can('decline', $invitation)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $invitation->update([
            'status' => InvitationStatus::Declined,
            'responded_at' => now(),
        ]);

        return $invitation->fresh();
    }
}
