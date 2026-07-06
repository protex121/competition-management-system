<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\InvitationStatus;
use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendTeamInvitationService
{
    /**
     * @param  array{email: string}  $data
     */
    public function execute(User $actor, Team $team, array $data): TeamInvitation
    {
        if (! $actor->can('invite', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $competition = $this->resolveCompetition($team);
        $this->assertCompetitionOpen($competition);

        $invitedUser = User::query()
            ->where('email', $data['email'])
            ->where('organization_id', $competition->organization_id)
            ->first();

        if ($invitedUser === null) {
            throw ValidationException::withMessages([
                'email' => ['No user with this email exists in your organization.'],
            ]);
        }

        if ($invitedUser->isDeactivated()) {
            throw ValidationException::withMessages([
                'email' => ['This user account is deactivated.'],
            ]);
        }

        if ($this->userHasActiveTeamInCompetition($invitedUser, $competition)) {
            throw ValidationException::withMessages([
                'email' => ['This user is already on a team in this competition.'],
            ]);
        }

        if ($this->hasDuplicatePendingInvitation($team, $invitedUser)) {
            throw ValidationException::withMessages([
                'email' => ['A pending invitation already exists for this user.'],
            ]);
        }

        if ($this->isTeamFull($team, $competition)) {
            throw ValidationException::withMessages([
                'email' => ['This team has reached its maximum size.'],
            ]);
        }

        return TeamInvitation::query()->create([
            'team_id' => $team->id,
            'invited_by_user_id' => $actor->id,
            'invited_user_id' => $invitedUser->id,
            'email' => $invitedUser->email,
            'token' => Str::random(64),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);
    }

    private function resolveCompetition(Team $team): Competition
    {
        return Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);
    }

    private function assertCompetitionOpen(Competition $competition): void
    {
        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['Invitations cannot be sent for a closed competition.'],
            ]);
        }
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

    private function hasDuplicatePendingInvitation(Team $team, User $invitedUser): bool
    {
        return TeamInvitation::query()
            ->where('team_id', $team->id)
            ->where('invited_user_id', $invitedUser->id)
            ->where('status', InvitationStatus::Pending)
            ->exists();
    }

    private function isTeamFull(Team $team, Competition $competition): bool
    {
        $maxSize = $competition->max_team_size;

        if ($maxSize === null) {
            return false;
        }

        $pendingCount = TeamInvitation::query()
            ->where('team_id', $team->id)
            ->where('status', InvitationStatus::Pending)
            ->count();

        return $team->activeMemberCount() + $pendingCount >= $maxSize;
    }
}
