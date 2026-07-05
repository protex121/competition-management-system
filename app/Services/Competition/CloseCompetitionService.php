<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Exceptions\Competition\InvalidCompetitionStatusTransitionException;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CloseCompetitionService
{
    public function execute(User $actor, Competition $competition): Competition
    {
        if (! $competition->isPublished() && ! $competition->isActive()) {
            throw new InvalidCompetitionStatusTransitionException(
                'Only published or active competitions can be closed.',
            );
        }

        return DB::transaction(function () use ($competition): Competition {
            $competition->update(['status' => CompetitionStatus::Closed]);

            $competition->categories()->update([
                'status' => CategoryStatus::Archived,
            ]);

            return $competition->fresh(['categories']);
        });
    }
}
