<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Collection;

class ListOrganizationCoachesService
{
    /**
     * @return Collection<int, array{id: int, name: string, email: string}>
     */
    public function execute(Competition $competition): Collection
    {
        return User::query()
            ->where('organization_id', $competition->organization_id)
            ->where('role', UserRole::Coach)
            ->whereNull('deactivated_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $coach) => [
                'id' => $coach->id,
                'name' => $coach->name,
                'email' => $coach->email,
            ]);
    }
}
