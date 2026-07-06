<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\InvitationStatus;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RevokeTeamInvitationService
{
    public function execute(User $actor, TeamInvitation $invitation): TeamInvitation
    {
        if (! $actor->can('revoke', $invitation)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $invitation->update([
            'status' => InvitationStatus::Revoked,
            'responded_at' => now(),
        ]);

        return $invitation->fresh();
    }
}
