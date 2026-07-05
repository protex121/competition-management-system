<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CompetitionStatus;
use App\Exceptions\Competition\InvalidCompetitionStatusTransitionException;
use App\Models\Competition;
use App\Models\User;

class ActivateCompetitionService
{
    public function execute(User $actor, Competition $competition): Competition
    {
        if (! $competition->isPublished()) {
            throw new InvalidCompetitionStatusTransitionException(
                'Only published competitions can be activated.',
            );
        }

        $competition->update(['status' => CompetitionStatus::Active]);

        return $competition->fresh();
    }
}
