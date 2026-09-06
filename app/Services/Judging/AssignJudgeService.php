<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Enums\CompetitionJudgeStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class AssignJudgeService
{
    /**
     * @param  array{user_id: int}  $data
     */
    public function execute(User $actor, Competition $competition, array $data): CompetitionJudge
    {
        if (! $actor->can('manage', [CompetitionJudge::class, $competition])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['Judges cannot be assigned for a closed competition.'],
            ]);
        }

        $judge = User::query()->find($data['user_id']);

        if ($judge === null) {
            throw ValidationException::withMessages([
                'user_id' => ['The selected judge does not exist.'],
            ]);
        }

        if ($judge->role !== UserRole::Judge) {
            throw ValidationException::withMessages([
                'user_id' => ['The selected user is not a judge.'],
            ]);
        }

        if ($judge->organization_id !== $competition->organization_id) {
            throw ValidationException::withMessages([
                'user_id' => ['The judge must belong to the same organization as the competition.'],
            ]);
        }

        if ($judge->isDeactivated()) {
            throw ValidationException::withMessages([
                'user_id' => ['This judge account is deactivated.'],
            ]);
        }

        $assignment = CompetitionJudge::withoutGlobalScopes()->updateOrCreate(
            ['competition_id' => $competition->id, 'user_id' => $judge->id],
            ['status' => CompetitionJudgeStatus::Active],
        );

        return $assignment->fresh(['user', 'competition']);
    }
}
