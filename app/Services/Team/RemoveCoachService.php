<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RemoveCoachService
{
    public function execute(User $actor, Team $team): Team
    {
        if (! $actor->can('assignCoach', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $competition = $this->resolveCompetition($team);
        $this->assertCompetitionOpen($competition);

        if ($team->coach_user_id === null) {
            throw ValidationException::withMessages([
                'coach' => ['This team does not have a coach assigned.'],
            ]);
        }

        $team->update(['coach_user_id' => null]);

        return $team->fresh(['coach', 'competition']);
    }

    private function resolveCompetition(Team $team): Competition
    {
        if ($team->relationLoaded('competition') && $team->getRelation('competition') !== null) {
            return $team->getRelation('competition');
        }

        return Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);
    }

    private function assertCompetitionOpen(Competition $competition): void
    {
        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['Coach cannot be removed for a closed competition.'],
            ]);
        }
    }
}
