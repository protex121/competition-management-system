<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CategoryStatus;
use App\Models\CompetitionCategory;
use App\Models\User;

class ActivateCategoryService
{
    public function execute(User $actor, CompetitionCategory $category): CompetitionCategory
    {
        $category->update(['status' => CategoryStatus::Active]);

        return $category->fresh();
    }
}
