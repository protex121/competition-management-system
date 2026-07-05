<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;

class RestoreUserService
{
    public function execute(User $user): void
    {
        $user->restore();
    }
}
