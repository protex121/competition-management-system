<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Enums\RegistrationStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\Team\CheckParticipantEligibilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterParticipantService
{
    public function __construct(
        private readonly CheckParticipantEligibilityService $eligibilityService,
    ) {}

    public function execute(User $actor, CompetitionCategory $category): Registration
    {
        if (! $actor->can('createIndividual', [Registration::class, $category])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($category->competition_id);

        $eligibility = $this->eligibilityService->execute($actor, $competition);

        if (! $eligibility->eligible) {
            throw ValidationException::withMessages(['category' => $eligibility->reasons]);
        }

        $config = EffectiveCategoryConfig::for($category);

        if (! $config->isRegistrationOpen(now())) {
            throw ValidationException::withMessages([
                'category' => ['Registration for this category is closed.'],
            ]);
        }

        if ($this->userHasActiveRegistrationInCompetition($actor, $competition)) {
            throw ValidationException::withMessages([
                'category' => ['You already have an active registration in this competition.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $category, $config): Registration {
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
                'user_id' => $actor->id,
                'status' => RegistrationStatus::Confirmed,
            ]);

            return $registration->load(['category.competition', 'user']);
        });
    }

    private function userHasActiveRegistrationInCompetition(User $user, Competition $competition): bool
    {
        return Registration::withoutGlobalScopes()
            ->join('competition_categories', 'competition_categories.id', '=', 'registrations.competition_category_id')
            ->where('registrations.user_id', $user->id)
            ->where('registrations.status', RegistrationStatus::Confirmed)
            ->where('competition_categories.competition_id', $competition->id)
            ->exists();
    }
}
