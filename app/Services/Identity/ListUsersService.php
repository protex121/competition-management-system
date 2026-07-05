<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUsersService
{
    public function execute(User $actor): LengthAwarePaginator
    {
        $query = User::query()
            ->with('organization')
            ->latest();

        if (! $actor->isSuperAdmin()) {
            $query->where('organization_id', $actor->organization_id);
        }

        return $query->paginate(15);
    }
}
