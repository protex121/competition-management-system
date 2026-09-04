<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\CompetitionCategory;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Collection;

class ListRegistrationsService
{
    /**
     * @return Collection<int, Registration>
     */
    public function execute(CompetitionCategory $category): Collection
    {
        return Registration::withoutGlobalScopes()
            ->where('competition_category_id', $category->id)
            ->with(['category.competition', 'user', 'team.captain'])
            ->orderByDesc('created_at')
            ->get();
    }
}
