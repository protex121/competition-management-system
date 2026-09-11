<?php

declare(strict_types=1);

namespace Tests\Feature\Judging\Concerns;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Submission;
use App\Models\User;

trait CreatesJudgingFixtures
{
    /**
     * @param  array<string, mixed>  $competitionAttributes
     * @return array{0: Organization, 1: Competition, 2: CompetitionCategory, 3: Registration, 4: Submission, 5: User}
     */
    protected function createFinalizedSubmissionSetup(array $competitionAttributes = []): array
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(array_merge([
            'organization_id' => $organization->id,
        ], $competitionAttributes));
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $registration = Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => $participant->id,
        ]);
        $submission = Submission::factory()->finalized()->create([
            'registration_id' => $registration->id,
            'description' => 'A working prototype.',
        ]);

        return [$organization, $competition, $category, $registration, $submission, $participant];
    }

    /**
     * @param  array<int, array{name: string, max_score: int}>  $criteria
     */
    protected function createRubricWithCriteria(Competition $competition, array $criteria = [['name' => 'Innovation', 'max_score' => 10]]): Rubric
    {
        $rubric = Rubric::withoutGlobalScopes()->firstOrCreate(['competition_id' => $competition->id]);

        foreach ($criteria as $criterion) {
            RubricCriterion::factory()->create([
                'rubric_id' => $rubric->id,
                'name' => $criterion['name'],
                'max_score' => $criterion['max_score'],
            ]);
        }

        return $rubric->refresh();
    }

    protected function createAssignedJudge(Organization $organization, Competition $competition): User
    {
        $judge = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);
        CompetitionJudge::factory()->create([
            'competition_id' => $competition->id,
            'user_id' => $judge->id,
        ]);

        return $judge;
    }
}
