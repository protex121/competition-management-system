<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\TeamMember;
use App\Models\User;

class CheckParticipantEligibilityService
{
    public function execute(User $user, Competition $competition): EligibilityResult
    {
        $reasons = [];

        if ($user->isDeactivated()) {
            $reasons[] = 'Your account is deactivated.';
        }

        if ($user->role !== UserRole::Participant && ! $user->isSuperAdmin()) {
            $reasons[] = 'Only participants can register for competitions.';
        }

        if ($user->organization_id === null || $user->organization_id !== $competition->organization_id) {
            $reasons[] = 'You must belong to the competition organization.';
        }

        if ($competition->isDraft()) {
            $reasons[] = 'This competition is not open for registration.';
        }

        if ($competition->isClosed()) {
            $reasons[] = 'This competition is closed.';
        }

        if ($competition->allowsTeams() && $this->userHasActiveTeamInCompetition($user, $competition)) {
            $reasons[] = 'You are already on a team in this competition.';
        }

        return $reasons === []
            ? EligibilityResult::eligible()
            : EligibilityResult::ineligible($reasons);
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
}
