<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;

class ReactivateUserService
{
    public function execute(User $user): void
    {
        $user->deactivated_at = null;
        $user->save();
    }
}
