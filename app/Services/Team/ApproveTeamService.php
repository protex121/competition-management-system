<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ApproveTeamService
{
    public function execute(User $actor, Team $team): Team
    {
        if (! $actor->can('approve', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return DB::transaction(function () use ($team): Team {
            $team->update([
                'status' => TeamStatus::Approved,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            return $team->fresh(['competition', 'captain', 'members.user']);
        });
    }
}
