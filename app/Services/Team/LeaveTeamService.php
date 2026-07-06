<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class LeaveTeamService
{
    public function execute(User $actor, Team $team): void
    {
        if (! $actor->can('view', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $membership = TeamMember::query()
            ->where('team_id', $team->id)
            ->where('user_id', $actor->id)
            ->where('status', TeamMemberStatus::Active)
            ->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'team' => ['You are not an active member of this team.'],
            ]);
        }

        if ($membership->isCaptain()) {
            throw ValidationException::withMessages([
                'team' => ['Transfer captaincy before leaving the team.'],
            ]);
        }

        $this->assertCompetitionOpen($team);

        $membership->update(['status' => TeamMemberStatus::Removed]);
    }

    private function assertCompetitionOpen(Team $team): void
    {
        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);

        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['You cannot leave a team in a closed competition.'],
            ]);
        }
    }
}
