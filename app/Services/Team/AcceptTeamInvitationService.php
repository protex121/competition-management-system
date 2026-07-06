<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\InvitationStatus;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptTeamInvitationService
{
    public function execute(User $actor, TeamInvitation $invitation): TeamMember
    {
        if (! $actor->can('accept', $invitation)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation has expired.'],
            ]);
        }

        $team = $this->resolveTeam($invitation);
        $competition = $this->resolveCompetition($team);

        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['This competition is closed.'],
            ]);
        }

        if (! $team->isForming()) {
            throw ValidationException::withMessages([
                'team' => ['This team is no longer accepting members.'],
            ]);
        }

        if ($this->userHasActiveTeamInCompetition($actor, $competition)) {
            throw ValidationException::withMessages([
                'team' => ['You are already on a team in this competition.'],
            ]);
        }

        if ($this->isTeamFull($team, $competition)) {
            throw ValidationException::withMessages([
                'team' => ['This team has reached its maximum size.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $invitation, $team): TeamMember {
            $member = TeamMember::query()->create([
                'team_id' => $team->id,
                'user_id' => $actor->id,
                'role' => TeamMemberRole::Member,
                'status' => TeamMemberStatus::Active,
                'joined_at' => now(),
            ]);

            $invitation->update([
                'status' => InvitationStatus::Accepted,
                'responded_at' => now(),
            ]);

            return $member->load('user');
        });
    }

    private function resolveTeam(TeamInvitation $invitation): Team
    {
        if ($invitation->relationLoaded('team') && $invitation->getRelation('team') !== null) {
            return $invitation->getRelation('team');
        }

        return Team::withoutGlobalScopes()->findOrFail($invitation->team_id);
    }

    private function resolveCompetition(Team $team): Competition
    {
        if ($team->relationLoaded('competition') && $team->getRelation('competition') !== null) {
            return $team->getRelation('competition');
        }

        return Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);
    }

    private function userHasActiveTeamInCompetition(User $user, Competition $competition): bool
    {
        return TeamMember::query()
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', $user->id)
            ->where('team_members.status', TeamMemberStatus::Active)
            ->where('teams.competition_id', $competition->id)
            ->whereNull('teams.deleted_at')
            ->exists();
    }

    private function isTeamFull(Team $team, Competition $competition): bool
    {
        $maxSize = $competition->max_team_size;

        if ($maxSize === null) {
            return false;
        }

        return $team->activeMemberCount() >= $maxSize;
    }
}
