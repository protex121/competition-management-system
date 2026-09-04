<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Enums\RegistrationStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\CheckTeamEligibilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterTeamService
{
    public function __construct(
        private readonly CheckTeamEligibilityService $eligibilityService,
    ) {}

    public function execute(User $actor, Team $team, CompetitionCategory $category): Registration
    {
        if (! $actor->can('createForTeam', [Registration::class, $team, $category])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($category->competition_id);

        $eligibility = $this->eligibilityService->execute($team, $competition);

        if (! $eligibility->eligible) {
            throw ValidationException::withMessages(['category' => $eligibility->reasons]);
        }

        $config = EffectiveCategoryConfig::for($category);

        if (! $config->isRegistrationOpen(now())) {
            throw ValidationException::withMessages([
                'category' => ['Registration for this category is closed.'],
            ]);
        }

        if ($this->teamHasActiveRegistration($team)) {
            throw ValidationException::withMessages([
                'category' => ['This team already has an active registration.'],
            ]);
        }

        return DB::transaction(function () use ($team, $category, $config): Registration {
            $confirmedCount = Registration::withoutGlobalScopes()
                ->where('competition_category_id', $category->id)
                ->where('status', RegistrationStatus::Confirmed)
                ->lockForUpdate()
                ->count();

            if (! $config->hasCapacity($confirmedCount)) {
                throw ValidationException::withMessages([
                    'category' => ['This category has reached its registration capacity.'],
                ]);
            }

            $registration = Registration::withoutGlobalScopes()->create([
                'competition_category_id' => $category->id,
                'team_id' => $team->id,
                'status' => RegistrationStatus::Confirmed,
            ]);

            return $registration->load(['category.competition', 'team']);
        });
    }

    private function teamHasActiveRegistration(Team $team): bool
    {
        return Registration::withoutGlobalScopes()
            ->where('team_id', $team->id)
            ->where('status', RegistrationStatus::Confirmed)
            ->exists();
    }
}
