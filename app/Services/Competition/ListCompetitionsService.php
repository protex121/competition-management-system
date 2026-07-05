<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCompetitionsService
{
    public function execute(User $actor): LengthAwarePaginator
    {
        $query = Competition::withoutGlobalScope(OrganizationScope::class)
            ->with(['organization', 'categories'])
            ->latest();

        if (! $actor->isSuperAdmin()) {
            $query->where('organization_id', $actor->organization_id);
        }

        return $query->paginate(15);
    }
}
