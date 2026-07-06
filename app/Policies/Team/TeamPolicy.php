<?php

declare(strict_types=1);

namespace App\Policies\Team;

use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->isOrganizer() || $actor->role === UserRole::Participant;
    }

    public function view(User $actor, Team $team): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($this->isOrganizerForTeam($actor, $team)) {
            return true;
        }

        return $this->isActiveMember($actor, $team);
    }

    public function create(User $actor, Competition $competition): bool
    {
        if ($competition->isClosed() || $competition->isDraft()) {
            return false;
        }

        if (! $competition->allowsTeams()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->role === UserRole::Participant
            && $actor->organization_id !== null
            && $actor->organization_id === $competition->organization_id;
    }

    public function update(User $actor, Team $team): bool
    {
        if (! $team->isForming() && ! $team->isRejected()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $this->isCaptain($actor, $team);
    }

    public function delete(User $actor, Team $team): bool
    {
        if (! $team->isForming()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($this->isOrganizerForTeam($actor, $team)) {
            $competition = $this->resolveCompetition($team);

            return $competition->isDraft();
        }

        return $this->isCaptain($actor, $team);
    }

    public function submit(User $actor, Team $team): bool
    {
        if (! $team->isForming() && ! $team->isRejected()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $this->isCaptain($actor, $team);
    }

    public function approve(User $actor, Team $team): bool
    {
        if (! $team->isPendingApproval()) {
            return false;
        }

        return $actor->isSuperAdmin() || $this->isOrganizerForTeam($actor, $team);
    }

    public function reject(User $actor, Team $team): bool
    {
        return $this->approve($actor, $team);
    }

    public function invite(User $actor, Team $team): bool
    {
        if (! $team->isForming()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($this->isOrganizerForTeam($actor, $team)) {
            return true;
        }

        return $this->isCaptain($actor, $team);
    }

    public function manageMembers(User $actor, Team $team): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($this->isOrganizerForTeam($actor, $team)) {
            return true;
        }

        return $this->isCaptain($actor, $team);
    }

    public function assignCoach(User $actor, Team $team): bool
    {
        if (! $team->isForming() && ! $team->isRejected()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($this->isOrganizerForTeam($actor, $team)) {
            return true;
        }

        return $this->isCaptain($actor, $team);
    }

    private function isCaptain(User $actor, Team $team): bool
    {
        return $team->captain_user_id === $actor->id;
    }

    private function isActiveMember(User $actor, Team $team): bool
    {
        return $team->members()
            ->where('user_id', $actor->id)
            ->where('status', TeamMemberStatus::Active)
            ->exists();
    }

    private function isOrganizerForTeam(User $actor, Team $team): bool
    {
        if (! $actor->isOrganizer() || $actor->organization_id === null) {
            return false;
        }

        $competition = $this->resolveCompetition($team);

        return $actor->organization_id === $competition->organization_id;
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
