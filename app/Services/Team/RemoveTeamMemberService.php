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

class RemoveTeamMemberService
{
    public function execute(User $actor, Team $team, TeamMember $member): void
    {
        if (! $actor->can('manageMembers', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if ($member->team_id !== $team->id) {
            throw ValidationException::withMessages([
                'member' => ['The selected member does not belong to this team.'],
            ]);
        }

        if ($member->isCaptain()) {
            throw ValidationException::withMessages([
                'member' => ['Transfer captaincy before removing the captain.'],
            ]);
        }

        if (! $member->isActive()) {
            throw ValidationException::withMessages([
                'member' => ['The selected member is not active on this team.'],
            ]);
        }

        $this->assertCompetitionOpen($team);

        $member->update(['status' => TeamMemberStatus::Removed]);
    }

    private function assertCompetitionOpen(Team $team): void
    {
        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);

        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['Team membership cannot be changed for a closed competition.'],
            ]);
        }
    }
}
