<?php

declare(strict_types=1);

namespace App\Policies\Registration;

use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Scopes\CompetitionOrganizationScope;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\User;

class RegistrationPolicy
{
    public function createIndividual(User $actor, CompetitionCategory $category): bool
    {
        $competition = $this->resolveCompetition($category);

        if (! $competition->allowsIndividual()) {
            return false;
        }

        if ($competition->isClosed() || $competition->isDraft()) {
            return false;
        }

        if (! $category->isActive()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->role === UserRole::Participant
            && $actor->organization_id !== null
            && $actor->organization_id === $competition->organization_id;
    }

    public function createForTeam(User $actor, Team $team, CompetitionCategory $category): bool
    {
        $competition = $this->resolveCompetition($category);

        if ($team->competition_id !== $competition->id) {
            return false;
        }

        if (! $competition->allowsTeams()) {
            return false;
        }

        if ($competition->isClosed() || $competition->isDraft()) {
            return false;
        }

        if (! $category->isActive() || ! $team->isApproved()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $team->captain_user_id === $actor->id;
    }

    public function view(User $actor, Registration $registration): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($this->isOrganizerForRegistration($actor, $registration)) {
            return true;
        }

        if ($registration->user_id === $actor->id) {
            return true;
        }

        if ($registration->team_id === null) {
            return false;
        }

        return Team::withoutGlobalScope(CompetitionOrganizationScope::class)
            ->whereKey($registration->team_id)
            ->whereHas('members', function ($query) use ($actor): void {
                $query->where('user_id', $actor->id)
                    ->where('status', TeamMemberStatus::Active);
            })
            ->exists();
    }

    public function viewAny(User $actor, Competition $competition): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isOrganizer()
            && $actor->organization_id !== null
            && $actor->organization_id === $competition->organization_id;
    }

    public function withdraw(User $actor, Registration $registration): bool
    {
        if (! $registration->isConfirmed()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($registration->user_id === $actor->id) {
            return true;
        }

        if ($registration->team_id === null) {
            return false;
        }

        $team = $registration->relationLoaded('team') && $registration->getRelation('team') !== null
            ? $registration->getRelation('team')
            : Team::withoutGlobalScope(CompetitionOrganizationScope::class)->find($registration->team_id);

        return $team !== null && $team->captain_user_id === $actor->id;
    }

    private function resolveCompetition(CompetitionCategory $category): Competition
    {
        if ($category->relationLoaded('competition') && $category->getRelation('competition') !== null) {
            return $category->getRelation('competition');
        }

        return Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($category->competition_id);
    }

    private function isOrganizerForRegistration(User $actor, Registration $registration): bool
    {
        if (! $actor->isOrganizer() || $actor->organization_id === null) {
            return false;
        }

        $category = $registration->relationLoaded('category')
            ? $registration->category
            : $registration->category()->first();

        if ($category === null) {
            return false;
        }

        $competition = $this->resolveCompetition($category);

        return $actor->organization_id === $competition->organization_id;
    }
}
