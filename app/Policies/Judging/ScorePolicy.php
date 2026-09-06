<?php

declare(strict_types=1);

namespace App\Policies\Judging;

use App\Enums\CompetitionJudgeStatus;
use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\Registration;
use App\Models\Scopes\CompetitionOrganizationScope;
use App\Models\Scopes\OrganizationScope;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;

class ScorePolicy
{
    public function manage(User $actor, Submission $submission): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if (! $actor->isJudge()) {
            return false;
        }

        if (! $submission->isFinalized()) {
            return false;
        }

        $registration = $this->resolveRegistration($submission);
        $competition = $this->resolveCompetition($registration);

        if ($competition->isClosed()) {
            return false;
        }

        if (! $this->isAssignedJudge($actor, $competition)) {
            return false;
        }

        return ! $this->isOwnerOrActiveMember($actor, $registration);
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

    private function isAssignedJudge(User $actor, Competition $competition): bool
    {
        return CompetitionJudge::withoutGlobalScope(CompetitionOrganizationScope::class)
            ->where('competition_id', $competition->id)
            ->where('user_id', $actor->id)
            ->where('status', CompetitionJudgeStatus::Active)
            ->exists();
    }

    private function isOwnerOrActiveMember(User $actor, Registration $registration): bool
    {
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

    private function resolveRegistration(Submission $submission): Registration
    {
        return $submission->relationLoaded('registration') && $submission->getRelation('registration') !== null
            ? $submission->getRelation('registration')
            : Registration::withoutGlobalScopes()->findOrFail($submission->registration_id);
    }

    private function resolveCompetition(Registration $registration): Competition
    {
        $category = $registration->relationLoaded('category') && $registration->getRelation('category') !== null
            ? $registration->getRelation('category')
            : CompetitionCategory::query()->findOrFail($registration->competition_category_id);

        if ($category->relationLoaded('competition') && $category->getRelation('competition') !== null) {
            return $category->getRelation('competition');
        }

        return Competition::withoutGlobalScope(OrganizationScope::class)->findOrFail($category->competition_id);
    }
}
