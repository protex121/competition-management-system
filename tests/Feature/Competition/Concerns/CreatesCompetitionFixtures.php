<?php

declare(strict_types=1);

namespace Tests\Feature\Competition\Concerns;

use App\Models\Competition;
use App\Models\Organization;
use App\Models\User;

trait CreatesCompetitionFixtures
{
    /**
     * @return array{0: Organization, 1: User}
     */
    protected function createOrganizerContext(): array
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        return [$organization, $organizer];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createCompetitionFor(Organization $organization, array $attributes = []): Competition
    {
        return Competition::factory()->create(array_merge([
            'organization_id' => $organization->id,
        ], $attributes));
    }
}
