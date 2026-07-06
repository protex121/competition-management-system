<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Models\CompetitionCategory;

class DeleteCategoryService
{
    public function execute(CompetitionCategory $category): void
    {
        $category->delete();
    }
}
