<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Enums\UserRole;
use App\Models\User;

class UpdateUserService
{
    /**
     * @param  array{name: string, email: string, role: UserRole|string}  $data
     */
    public function execute(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }
}
