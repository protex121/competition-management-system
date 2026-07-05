<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCompetitionService
{
    /**
     * @param  array{
     *     name: string,
     *     slug?: string|null,
     *     description?: string|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     registration_starts_at?: string|null,
     *     registration_ends_at?: string|null,
     *     max_participants?: int|null,
     *     organization_id?: int|null,
     * }  $data
     */
    public function execute(User $actor, array $data): Competition
    {
        $organizationId = $actor->isSuperAdmin()
            ? $data['organization_id']
            : $actor->organization_id;

        return DB::transaction(function () use ($data, $organizationId): Competition {
            $slug = $data['slug'] ?? $this->generateUniqueSlug($organizationId, $data['name']);

            $competition = Competition::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $organizationId,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'status' => CompetitionStatus::Draft,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'registration_starts_at' => $data['registration_starts_at'] ?? null,
                'registration_ends_at' => $data['registration_ends_at'] ?? null,
                'max_participants' => $data['max_participants'] ?? null,
            ]);

            CompetitionCategory::query()->create([
                'competition_id' => $competition->id,
                'name' => 'General',
                'slug' => 'general',
                'status' => CategoryStatus::Draft,
                'sort_order' => 0,
                'is_default' => true,
            ]);

            return $competition->load('categories');
        });
    }

    private function generateUniqueSlug(int $organizationId, string $name): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'competition';
        $slug = $baseSlug;
        $suffix = 2;

        while (Competition::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
