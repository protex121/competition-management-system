<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;

class UpdateProfileService
{
    /**
     * Update the user's own profile. Role and organization are intentionally
     * not accepted here — self-service must never change privileges or tenant.
     *
     * @param  array{name: string, email: string}  $data
     */
    public function execute(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }
}
