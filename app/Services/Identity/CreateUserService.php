<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Enums\UserRole;
use App\Models\User;

class CreateUserService
{
    /**
     * @param  array{name: string, email: string, password: string, role: UserRole|string, organization_id?: int|null}  $data
     */
    public function execute(User $actor, array $data): User
    {
        $organizationId = $actor->isSuperAdmin()
            ? $data['organization_id']
            : $actor->organization_id;

        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'organization_id' => $organizationId,
            'role' => $data['role'],
        ]);
    }
}
