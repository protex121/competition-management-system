<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;

class DeleteUserService
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
