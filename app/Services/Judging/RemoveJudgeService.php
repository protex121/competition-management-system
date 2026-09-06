<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Enums\CompetitionJudgeStatus;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RemoveJudgeService
{
    public function execute(User $actor, Competition $competition, CompetitionJudge $assignment): CompetitionJudge
    {
        if (! $actor->can('manage', [CompetitionJudge::class, $competition])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if ($assignment->competition_id !== $competition->id) {
            throw ValidationException::withMessages([
                'judge' => ['This judge is not assigned to this competition.'],
            ]);
        }

        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['Judges cannot be revoked for a closed competition.'],
            ]);
        }

        $assignment->update(['status' => CompetitionJudgeStatus::Removed]);

        return $assignment->fresh(['user', 'competition']);
    }
}
