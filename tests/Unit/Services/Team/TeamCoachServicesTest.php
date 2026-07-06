<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\Enums\TeamStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\AssignCoachService;
use App\Services\Team\RemoveCoachService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamCoachServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_coach_service_assigns_coach_to_team(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);
        $coach = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Coach,
        ]);

        $updated = (new AssignCoachService)->execute($captain, $team, [
            'user_id' => $coach->id,
        ]);

        $this->assertSame($coach->id, $updated->coach_user_id);
    }

    public function test_assign_coach_service_rejects_non_coach_user(): void
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        Competition::withoutGlobalScope(OrganizationScope::class)
            ->where('id', $team->competition_id)
            ->update(['organization_id' => $organization->id]);

        $this->expectException(ValidationException::class);

        (new AssignCoachService)->execute($captain, $team, [
            'user_id' => $participant->id,
        ]);
    }

    public function test_assign_coach_service_rejects_cross_org_coach(): void
    {
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $coach = User::factory()->create(['role' => UserRole::Coach]);

        $this->expectException(ValidationException::class);

        (new AssignCoachService)->execute($captain, $team, [
            'user_id' => $coach->id,
        ]);
    }

    public function test_assign_coach_service_denies_when_team_not_editable(): void
    {
        $team = Team::factory()->approved()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $coach = User::factory()->create(['role' => UserRole::Coach]);

        $this->expectException(AuthorizationException::class);

        (new AssignCoachService)->execute($captain, $team, [
            'user_id' => $coach->id,
        ]);
    }

    public function test_remove_coach_service_clears_coach(): void
    {
        $organization = Organization::factory()->create();
        $coach = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Coach,
        ]);
        $team = Team::factory()->create([
            'coach_user_id' => $coach->id,
            'status' => TeamStatus::Forming,
        ]);
        $captain = User::query()->findOrFail($team->captain_user_id);
        Competition::withoutGlobalScope(OrganizationScope::class)
            ->where('id', $team->competition_id)
            ->update(['organization_id' => $organization->id]);

        $updated = (new RemoveCoachService)->execute($captain, $team);

        $this->assertNull($updated->coach_user_id);
    }

    public function test_remove_coach_service_blocks_when_no_coach_assigned(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Forming]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->expectException(ValidationException::class);

        (new RemoveCoachService)->execute($captain, $team);
    }
}
