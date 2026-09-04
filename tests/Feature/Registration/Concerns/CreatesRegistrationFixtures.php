<?php

declare(strict_types=1);

namespace Tests\Feature\Registration\Concerns;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\User;

trait CreatesRegistrationFixtures
{
    protected function createActiveCategoryFor(Competition $competition, array $attributes = []): CompetitionCategory
    {
        return CompetitionCategory::factory()->active()->create(array_merge([
            'competition_id' => $competition->id,
        ], $attributes));
    }

    protected function createParticipantFor(Organization $organization): User
    {
        return User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
    }
}
