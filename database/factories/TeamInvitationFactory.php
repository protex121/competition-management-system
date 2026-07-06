<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    protected $model = TeamInvitation::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'invited_by_user_id' => User::factory(),
            'invited_user_id' => User::factory(),
            'email' => fake()->safeEmail(),
            'token' => Str::random(64),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
            'responded_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TeamInvitation $invitation): void {
            $invitedUser = User::query()->find($invitation->invited_user_id);

            if ($invitedUser !== null) {
                $invitation->email = $invitedUser->email;
            }
        });
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
            'status' => InvitationStatus::Expired,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'status' => InvitationStatus::Accepted,
            'responded_at' => now(),
        ]);
    }
}
