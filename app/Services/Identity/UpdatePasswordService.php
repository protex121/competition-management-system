<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;

class UpdatePasswordService
{
    /**
     * @param  array{password: string}  $data
     */
    public function execute(User $user, array $data): User
    {
        $user->update([
            'password' => $data['password'],
        ]);

        return $user;
    }
}
