<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowPublicCompetitionService
{
    /**
     * @return array{
     *     organization: array{id: int, name: string, slug: string},
     *     competition: array<string, mixed>,
     *     categories: array<int, array<string, mixed>>,
     * }
     */
    public function execute(string $organizationSlug, string $competitionSlug): array
    {
        $organization = Organization::query()
            ->where('slug', $organizationSlug)
            ->first();

        if ($organization === null) {
            throw (new ModelNotFoundException)->setModel(Organization::class);
        }

        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id)
            ->where('slug', $competitionSlug)
            ->first();

        if ($competition === null) {
            throw (new ModelNotFoundException)->setModel(Competition::class);
        }

        if (! $this->isPubliclyVisible($competition)) {
            abort(404);
        }

        $categoryStatuses = $competition->isClosed()
            ? [CategoryStatus::Archived]
            : [CategoryStatus::Active];

        $categories = $competition->categories()
            ->whereIn('status', $categoryStatuses)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'max_participants' => $category->max_participants,
                'registration_ends_at' => $category->registration_ends_at?->toISOString(),
            ])
            ->values()
            ->all();

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
                'description' => $competition->description,
                'status' => $competition->status->value,
                'starts_at' => $competition->starts_at?->toISOString(),
                'ends_at' => $competition->ends_at?->toISOString(),
                'registration_starts_at' => $competition->registration_starts_at?->toISOString(),
                'registration_ends_at' => $competition->registration_ends_at?->toISOString(),
                'max_participants' => $competition->max_participants,
                'registration_mode' => $competition->registration_mode->value,
                'min_team_size' => $competition->min_team_size,
                'max_team_size' => $competition->max_team_size,
                'requires_coach' => $competition->requires_coach,
            ],
            'categories' => $categories,
        ];
    }

    private function isPubliclyVisible(Competition $competition): bool
    {
        return in_array($competition->status, [
            CompetitionStatus::Published,
            CompetitionStatus::Active,
            CompetitionStatus::Closed,
        ], true);
    }
}
