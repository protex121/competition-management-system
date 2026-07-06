<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CategoryStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\User;
use Illuminate\Support\Str;

class CreateCategoryService
{
    /**
     * @param  array{
     *     name: string,
     *     slug?: string|null,
     *     description?: string|null,
     *     max_participants?: int|null,
     *     registration_ends_at?: string|null,
     *     sort_order?: int|null,
     * }  $data
     */
    public function execute(User $actor, Competition $competition, array $data): CompetitionCategory
    {
        $slug = $data['slug'] ?? $this->generateUniqueSlug($competition->id, $data['name']);

        $sortOrder = $data['sort_order'] ?? ((int) $competition->categories()->max('sort_order')) + 1;

        return CompetitionCategory::query()->create([
            'competition_id' => $competition->id,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => CategoryStatus::Draft,
            'sort_order' => $sortOrder,
            'max_participants' => $data['max_participants'] ?? null,
            'registration_ends_at' => $data['registration_ends_at'] ?? null,
            'is_default' => false,
        ]);
    }

    private function generateUniqueSlug(int $competitionId, string $name): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'category';
        $slug = $baseSlug;
        $suffix = 2;

        while (CompetitionCategory::query()
            ->where('competition_id', $competitionId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
