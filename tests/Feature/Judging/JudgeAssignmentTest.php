<?php

declare(strict_types=1);

namespace Tests\Feature\Judging;

use App\Enums\CompetitionJudgeStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_assign_and_revoke_a_judge(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $judge = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);

        $this->actingAs($organizer)
            ->post(route('competitions.judges.store', $competition), ['user_id' => $judge->id])
            ->assertRedirect();

        $this->assertDatabaseHas('competition_judges', [
            'competition_id' => $competition->id,
            'user_id' => $judge->id,
            'status' => CompetitionJudgeStatus::Active->value,
        ]);

        $assignment = CompetitionJudge::withoutGlobalScopes()->firstWhere('user_id', $judge->id);

        $this->actingAs($organizer)
            ->delete(route('competitions.judges.destroy', [$competition, $assignment]))
            ->assertRedirect();

        $this->assertDatabaseHas('competition_judges', [
            'id' => $assignment->id,
            'status' => CompetitionJudgeStatus::Removed->value,
        ]);
    }

    public function test_reassigning_a_removed_judge_reactivates_the_row(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $judge = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);
        $assignment = CompetitionJudge::factory()->removed()->create([
            'competition_id' => $competition->id,
            'user_id' => $judge->id,
        ]);

        $this->actingAs($organizer)
            ->post(route('competitions.judges.store', $competition), ['user_id' => $judge->id])
            ->assertRedirect();

        $this->assertDatabaseCount('competition_judges', 1);
        $this->assertDatabaseHas('competition_judges', [
            'id' => $assignment->id,
            'status' => CompetitionJudgeStatus::Active->value,
        ]);
    }

    public function test_cannot_assign_non_judge_role_user(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($organizer)
            ->post(route('competitions.judges.store', $competition), ['user_id' => $participant->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('competition_judges', 0);
    }

    public function test_cannot_assign_judge_from_other_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $outsiderJudge = User::factory()->create(['role' => UserRole::Judge]);

        $this->actingAs($organizer)
            ->post(route('competitions.judges.store', $competition), ['user_id' => $outsiderJudge->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('competition_judges', 0);
    }

    public function test_cannot_assign_deactivated_judge(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $judge = User::factory()->deactivated()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);

        $this->actingAs($organizer)
            ->post(route('competitions.judges.store', $competition), ['user_id' => $judge->id])
            ->assertSessionHasErrors('user_id');
    }

    public function test_organizer_from_other_org_cannot_assign_judge(): void
    {
        $competition = Competition::factory()->published()->create();
        $organizer = User::factory()->organizer()->create();
        $judge = User::factory()->create(['role' => UserRole::Judge]);

        $this->actingAs($organizer)
            ->post(route('competitions.judges.store', $competition), ['user_id' => $judge->id])
            ->assertNotFound();
    }
}
