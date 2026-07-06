<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamStatus;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitTeamForApprovalService
{
    public function execute(User $actor, Team $team): Team
    {
        if (! $actor->can('submit', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $competition = $this->resolveCompetition($team);

        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['Teams cannot be submitted for a closed competition.'],
            ]);
        }

        $minSize = $competition->min_team_size ?? 1;

        if ($team->activeMemberCount() < $minSize) {
            throw ValidationException::withMessages([
                'team' => ["Team must have at least {$minSize} members before submission."],
            ]);
        }

        return DB::transaction(function () use ($team): Team {
            $team->update([
                'status' => TeamStatus::PendingApproval,
                'submitted_at' => now(),
                'rejection_reason' => null,
            ]);

            return $team->fresh(['competition', 'captain', 'members.user']);
        });
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
