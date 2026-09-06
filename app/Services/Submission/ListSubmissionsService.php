<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Models\CompetitionCategory;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Collection;

class ListSubmissionsService
{
    /**
     * @return Collection<int, Submission>
     */
    public function execute(CompetitionCategory $category): Collection
    {
        return Submission::withoutGlobalScopes()
            ->whereHas('registration', function ($query) use ($category): void {
                $query->where('competition_category_id', $category->id);
            })
            ->with(['registration.user', 'registration.team'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
