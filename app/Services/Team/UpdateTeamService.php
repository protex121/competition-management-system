<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateTeamService
{
    /**
     * @param  array{name: string}  $data
     */
    public function execute(User $actor, Team $team, array $data): Team
    {
        if (! $actor->can('update', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $team->update([
            'name' => $data['name'],
        ]);

        return $team->fresh(['competition', 'captain', 'members.user']);
    }
}
