<?php

declare(strict_types=1);

namespace App\Policies\Judging;

use App\Enums\CompetitionJudgeStatus;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Models\Scopes\CompetitionOrganizationScope;
use App\Models\User;

class RubricPolicy
{
    public function manageCriteria(User $actor, Competition $competition): bool
    {
        return $this->canManageCompetition($actor, $competition);
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
