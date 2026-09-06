<?php

declare(strict_types=1);

namespace Tests\Feature\Submission\Concerns;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;

trait CreatesSubmissionFixtures
{
    /**
     * @param  array<string, mixed>  $competitionAttributes
     * @return array{0: Organization, 1: Competition, 2: CompetitionCategory}
     */
    protected function createPublishedCompetitionWithActiveCategory(array $competitionAttributes = []): array
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(array_merge([
            'organization_id' => $organization->id,
        ], $competitionAttributes));
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);

        return [$organization, $competition, $category];
    }

    protected function createParticipantFor(Organization $organization): User
    {
        return User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
    }

    protected function createConfirmedRegistration(CompetitionCategory $category, User $participant): Registration
    {
        return Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => $participant->id,
        ]);
    }
}
