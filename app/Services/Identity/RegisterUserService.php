<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterUserService
{
    /**
     * @param  array{name: string, email: string, password: string, organization_name: string}  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $organization = Organization::query()->create([
                'name' => $data['organization_name'],
                'slug' => $this->generateUniqueSlug($data['organization_name']),
            ]);

            return User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'organization_id' => $organization->id,
                'role' => UserRole::Organizer,
            ]);
        });
    }

    private function generateUniqueSlug(string $organizationName): string
    {
        $baseSlug = Str::slug($organizationName);
        $slug = $baseSlug !== '' ? $baseSlug : 'organization';
        $suffix = 1;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $baseSlug.'-'.$suffix;
        }

        return $slug;
    }
}
