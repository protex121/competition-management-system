<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RejectTeamService
{
    /**
     * @param  array{rejection_reason?: string|null}  $data
     */
    public function execute(User $actor, Team $team, array $data = []): Team
    {
        if (! $actor->can('reject', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return DB::transaction(function () use ($team, $data): Team {
            $team->update([
                'status' => TeamStatus::Rejected,
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'approved_at' => null,
            ]);

            return $team->fresh(['competition', 'captain', 'members.user']);
        });
    }
}
