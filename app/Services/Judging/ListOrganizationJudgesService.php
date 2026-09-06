<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Collection;

class ListOrganizationJudgesService
{
    /**
     * @return Collection<int, array{id: int, name: string, email: string}>
     */
    public function execute(Competition $competition): Collection
    {
        return User::query()
            ->where('organization_id', $competition->organization_id)
            ->where('role', UserRole::Judge)
            ->whereNull('deactivated_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $judge) => [
                'id' => $judge->id,
                'name' => $judge->name,
                'email' => $judge->email,
            ]);
    }
}
