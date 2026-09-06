<?php

declare(strict_types=1);

namespace App\Policies\Submission;

use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Scopes\CompetitionOrganizationScope;
use App\Models\Scopes\OrganizationScope;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;

class SubmissionPolicy
{
    public function manage(User $actor, Registration $registration): bool
    {
        if (! $registration->isConfirmed()) {
            return false;
        }

        $competition = $this->resolveCompetition($registration);

        if ($competition->isClosed() || $competition->isDraft()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $this->isOwnerOrActiveMember($actor, $registration);
    }

    public function update(User $actor, Submission $submission): bool
    {
        if (! $submission->isDraft()) {
            return false;
        }

        $registration = $this->resolveRegistration($submission);

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $this->isOwnerOrActiveMember($actor, $registration);
    }

    public function finalize(User $actor, Submission $submission): bool
    {
        return $this->update($actor, $submission);
    }

    public function view(User $actor, Submission $submission): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        $registration = $this->resolveRegistration($submission);

        if ($this->isOrganizerForRegistration($actor, $registration)) {
            return true;
        }

        return $this->isOwnerOrActiveMember($actor, $registration);
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

    private function isOrganizerForRegistration(User $actor, Registration $registration): bool
    {
        if (! $actor->isOrganizer() || $actor->organization_id === null) {
            return false;
        }

        $competition = $this->resolveCompetition($registration);

        return $actor->organization_id === $competition->organization_id;
    }

    private function resolveRegistration(Submission $submission): Registration
    {
        if ($submission->relationLoaded('registration') && $submission->getRelation('registration') !== null) {
            return $submission->getRelation('registration');
        }

        return Registration::withoutGlobalScopes()->findOrFail($submission->registration_id);
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
