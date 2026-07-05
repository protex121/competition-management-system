<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CompetitionStatus;
use App\Events\Competition\CompetitionPublished;
use App\Exceptions\Competition\InvalidCompetitionStatusTransitionException;
use App\Models\Competition;
use App\Models\User;

class PublishCompetitionService
{
    public function execute(User $actor, Competition $competition): Competition
    {
        if (! $competition->isDraft()) {
            throw new InvalidCompetitionStatusTransitionException(
                'Only draft competitions can be published.',
            );
        }

        $competition->update(['status' => CompetitionStatus::Published]);

        event(new CompetitionPublished($competition));

        return $competition->fresh();
    }
}
