<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Models\CompetitionCategory;

class UpdateCategoryService
{
    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     description?: string|null,
     *     max_participants?: int|null,
     *     registration_ends_at?: string|null,
     *     sort_order?: int|null,
     * }  $data
     */
    public function execute(CompetitionCategory $category, array $data): CompetitionCategory
    {
        $category->fill([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'max_participants' => $data['max_participants'] ?? null,
            'registration_ends_at' => $data['registration_ends_at'] ?? null,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        $category->save();

        return $category;
    }
}
