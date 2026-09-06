<?php

declare(strict_types=1);

namespace App\Policies\Judging;

use App\Enums\CompetitionJudgeStatus;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Scopes\CompetitionOrganizationScope;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;

class RubricPolicy
{
    public function create(User $actor, Competition $competition): bool
    {
        return $this->canManageCompetition($actor, $competition);
    }

    public function update(User $actor, RubricCriterion $criterion): bool
    {
        return $this->canManageCompetition($actor, $this->resolveCompetition($criterion));
    }

    public function delete(User $actor, RubricCriterion $criterion): bool
    {
        return $this->update($actor, $criterion);
    }

    public function view(User $actor, Competition $competition): bool
    {
        if ($this->canManageCompetition($actor, $competition)) {
            return true;
        }

        if (! $actor->isJudge()) {
            return false;
        }

        return CompetitionJudge::withoutGlobalScope(CompetitionOrganizationScope::class)
            ->where('competition_id', $competition->id)
            ->where('user_id', $actor->id)
            ->where('status', CompetitionJudgeStatus::Active)
            ->exists();
    }

    private function resolveCompetition(RubricCriterion $criterion): Competition
    {
        $rubric = $criterion->relationLoaded('rubric') && $criterion->getRelation('rubric') !== null
            ? $criterion->getRelation('rubric')
            : Rubric::withoutGlobalScope(CompetitionOrganizationScope::class)->findOrFail($criterion->rubric_id);

        if ($rubric->relationLoaded('competition') && $rubric->getRelation('competition') !== null) {
            return $rubric->getRelation('competition');
        }

        return Competition::withoutGlobalScope(OrganizationScope::class)->findOrFail($rubric->competition_id);
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
