<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class AssignCoachService
{
    /**
     * @param  array{user_id: int}  $data
     */
    public function execute(User $actor, Team $team, array $data): Team
    {
        if (! $actor->can('assignCoach', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $competition = $this->resolveCompetition($team);
        $this->assertCompetitionOpen($competition);

        $coach = User::query()->find($data['user_id']);

        if ($coach === null) {
            throw ValidationException::withMessages([
                'user_id' => ['The selected coach does not exist.'],
            ]);
        }

        if ($coach->role !== UserRole::Coach) {
            throw ValidationException::withMessages([
                'user_id' => ['The selected user is not a coach.'],
            ]);
        }

        if ($coach->organization_id !== $competition->organization_id) {
            throw ValidationException::withMessages([
                'user_id' => ['The coach must belong to the same organization as the competition.'],
            ]);
        }

        if ($coach->isDeactivated()) {
            throw ValidationException::withMessages([
                'user_id' => ['This coach account is deactivated.'],
            ]);
        }

        $team->update(['coach_user_id' => $coach->id]);

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
                'competition' => ['Coach cannot be assigned for a closed competition.'],
            ]);
        }
    }
}
