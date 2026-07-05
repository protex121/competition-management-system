<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com', 'organization_id' => null],
            [
                'name' => 'Platform Admin',
                'password' => 'password',
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ],
        );
    }
}
