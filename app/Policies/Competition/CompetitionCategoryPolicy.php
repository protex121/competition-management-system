<?php

declare(strict_types=1);

namespace App\Policies\Competition;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;

class CompetitionCategoryPolicy
{
    public function create(User $actor, Competition $competition): bool
    {
        if ($competition->isClosed()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function update(User $actor, CompetitionCategory $category): bool
    {
        $competition = $this->resolveCompetition($category);

        if ($competition->isClosed()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function delete(User $actor, CompetitionCategory $category): bool
    {
        if ($category->isDefault()) {
            return false;
        }

        $competition = $this->resolveCompetition($category);

        if ($competition->isClosed()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function activate(User $actor, CompetitionCategory $category): bool
    {
        $competition = $this->resolveCompetition($category);

        if ($competition->isDraft() || $competition->isClosed()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function disable(User $actor, CompetitionCategory $category): bool
    {
        $competition = $this->resolveCompetition($category);

        if ($competition->isDraft() || $competition->isClosed()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    private function resolveCompetition(CompetitionCategory $category): Competition
    {
        if ($category->relationLoaded('competition') && $category->getRelation('competition') !== null) {
            return $category->getRelation('competition');
        }

        return Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($category->competition_id);
    }

    private function canManageCompetition(User $actor, Competition $competition): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isOrganizer()
            && $actor->organization_id !== null
            && $actor->organization_id === $competition->organization_id;
    }
}
