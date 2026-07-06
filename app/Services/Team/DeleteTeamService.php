<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteTeamService
{
    public function execute(User $actor, Team $team): void
    {
        if (! $actor->can('delete', $team)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $team->delete();
    }
}
