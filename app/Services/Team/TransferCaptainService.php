<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferCaptainService
{
    public function execute(User $actor, Team $team, TeamMember $member): Team
    {
        if (! $actor->can('manageMembers', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if (! $member->isActive()) {
            throw ValidationException::withMessages([
                'member' => ['The selected member is not active on this team.'],
            ]);
        }

        if ($member->isCaptain()) {
            throw ValidationException::withMessages([
                'member' => ['This member is already the captain.'],
            ]);
        }

        if ($member->team_id !== $team->id) {
            throw ValidationException::withMessages([
                'member' => ['The selected member does not belong to this team.'],
            ]);
        }

        $this->assertCompetitionOpen($team);

        return DB::transaction(function () use ($team, $member): Team {
            TeamMember::query()
                ->where('team_id', $team->id)
                ->where('role', TeamMemberRole::Captain)
                ->where('status', TeamMemberStatus::Active)
                ->update(['role' => TeamMemberRole::Member]);

            $member->update(['role' => TeamMemberRole::Captain]);

            $team->update(['captain_user_id' => $member->user_id]);

            return $team->fresh(['captain', 'members.user']);
        });
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
