<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\Competition;
use App\Models\Team;

class CheckTeamEligibilityService
{
    public function execute(Team $team, Competition $competition): EligibilityResult
    {
        $reasons = [];

        if (! $team->isApproved()) {
            $reasons[] = 'Team must be approved before registration.';
        }

        $memberCount = $team->activeMemberCount();
        $minSize = $competition->min_team_size;

        if ($minSize !== null && $memberCount < $minSize) {
            $reasons[] = "Team must have at least {$minSize} members.";
        }

        $maxSize = $competition->max_team_size;

        if ($maxSize !== null && $memberCount > $maxSize) {
            $reasons[] = "Team cannot exceed {$maxSize} members.";
        }

        if ($competition->requires_coach && $team->coach_user_id === null) {
            $reasons[] = 'Team must have an assigned coach.';
        }

        if ($competition->isClosed()) {
            $reasons[] = 'This competition is closed.';
        }

        return $reasons === []
            ? EligibilityResult::eligible()
            : EligibilityResult::ineligible($reasons);
    }
}
