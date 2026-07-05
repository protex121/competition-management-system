<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;

class DeactivateUserService
{
    public function execute(User $user): void
    {
        $user->deactivated_at = now();
        $user->save();
    }
}
