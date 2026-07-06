<?php

declare(strict_types=1);

namespace App\Policies\Team;

use App\Enums\InvitationStatus;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

class TeamInvitationPolicy
{
    public function view(User $actor, TeamInvitation $invitation): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($invitation->invited_user_id === $actor->id) {
            return true;
        }

        return $this->canManageTeamInvitations($actor, $invitation);
    }

    public function accept(User $actor, TeamInvitation $invitation): bool
    {
        if ($invitation->invited_user_id !== $actor->id) {
            return false;
        }

        return $invitation->status === InvitationStatus::Pending
            && ! $invitation->isExpired();
    }

    public function decline(User $actor, TeamInvitation $invitation): bool
    {
        if ($invitation->invited_user_id !== $actor->id) {
            return false;
        }

        return $invitation->status === InvitationStatus::Pending
            && ! $invitation->isExpired();
    }

    public function revoke(User $actor, TeamInvitation $invitation): bool
    {
        if ($invitation->status !== InvitationStatus::Pending) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $this->canManageTeamInvitations($actor, $invitation);
    }

    private function canManageTeamInvitations(User $actor, TeamInvitation $invitation): bool
    {
        $team = $this->resolveTeam($invitation);

        if ($actor->isOrganizer() && $actor->organization_id !== null) {
            $competition = $this->resolveCompetition($team);

            if ($actor->organization_id === $competition->organization_id) {
                return true;
            }
        }

        return $team->captain_user_id === $actor->id;
    }

    private function resolveTeam(TeamInvitation $invitation): Team
    {
        if ($invitation->relationLoaded('team') && $invitation->getRelation('team') !== null) {
            return $invitation->getRelation('team');
        }

        return Team::withoutGlobalScopes()
            ->findOrFail($invitation->team_id);
    }

    private function resolveCompetition(Team $team): Competition
    {
        if ($team->relationLoaded('competition') && $team->getRelation('competition') !== null) {
            return $team->getRelation('competition');
        }

        return Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);
    }
}
