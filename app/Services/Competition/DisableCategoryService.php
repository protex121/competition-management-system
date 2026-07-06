<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CategoryStatus;
use App\Models\CompetitionCategory;
use App\Models\User;

class DisableCategoryService
{
    public function execute(User $actor, CompetitionCategory $category): CompetitionCategory
    {
        $category->update(['status' => CategoryStatus::Disabled]);

        return $category->fresh();
    }
}
