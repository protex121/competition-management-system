<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RegistrationMode;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory()->state([
                'registration_mode' => RegistrationMode::Team,
                'min_team_size' => 2,
                'max_team_size' => 5,
            ]),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'captain_user_id' => User::factory(),
            'coach_user_id' => null,
            'status' => TeamStatus::Forming,
            'rejection_reason' => null,
            'submitted_at' => null,
            'approved_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Team $team): void {
            $competition = Competition::withoutGlobalScope(OrganizationScope::class)
                ->findOrFail($team->competition_id);
            $organizationId = $competition->organization_id;
            $captain = User::query()->find($team->captain_user_id);

            if ($captain === null || $captain->organization_id !== $organizationId) {
                $captain = User::factory()->create([
                    'organization_id' => $organizationId,
                ]);
                $team->update(['captain_user_id' => $captain->id]);
            }

            TeamMember::query()->firstOrCreate(
                [
                    'team_id' => $team->id,
                    'user_id' => $captain->id,
                ],
                [
                    'role' => TeamMemberRole::Captain,
                    'status' => TeamMemberStatus::Active,
                    'joined_at' => now(),
                ],
            );
        });
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (): array => [
            'status' => TeamStatus::PendingApproval,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => TeamStatus::Approved,
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => TeamStatus::Rejected,
            'submitted_at' => now()->subDay(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
