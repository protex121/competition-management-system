<?php

declare(strict_types=1);

namespace App\Services\Leaderboard;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\LeaderboardEntry;
use App\Models\Organization;
use App\Models\Scopes\CompetitionOrganizationScope;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowPublicLeaderboardService
{
    /**
     * @return array{
     *     organization: array{id: int, name: string, slug: string},
     *     competition: array{id: int, name: string, slug: string},
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

        if (! $competition->isClosed()) {
            abort(404);
        }

        $categories = CompetitionCategory::withoutGlobalScope(CompetitionOrganizationScope::class)
            ->where('competition_id', $competition->id)
            ->orderBy('sort_order')
            ->get();

        $entries = LeaderboardEntry::withoutGlobalScopes()
            ->whereIn('competition_category_id', $categories->pluck('id'))
            ->with([
                'submission' => fn ($query) => $query->withoutGlobalScopes(),
                'submission.registration' => fn ($query) => $query->withoutGlobalScopes(),
                'submission.registration.user',
                'submission.registration.team' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->orderBy('rank')
            ->get()
            ->groupBy('competition_category_id');

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
            ],
            'categories' => $categories->map(fn (CompetitionCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'entries' => $entries->get($category->id, collect())->map(fn (LeaderboardEntry $entry): array => [
                    'rank' => $entry->rank,
                    'registrant' => $entry->submission->registration->team?->name
                        ?? $entry->submission->registration->user?->name,
                    'type' => $entry->submission->registration->isTeam() ? 'team' : 'individual',
                    'aggregate_score' => (float) $entry->aggregate_score,
                    'judge_count' => $entry->judge_count,
                ])->values(),
            ])->values()->all(),
        ];
    }
}
