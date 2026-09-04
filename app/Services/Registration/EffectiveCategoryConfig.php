<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Scopes\OrganizationScope;
use Carbon\CarbonInterface;

/**
 * Resolves a category's effective registration configuration, inheriting
 * from the parent competition wherever the category has no override (ADR-0012).
 */
readonly class EffectiveCategoryConfig
{
    private function __construct(
        public ?int $maxParticipants,
        public ?CarbonInterface $registrationStartsAt,
        public ?CarbonInterface $registrationEndsAt,
    ) {}

    public static function for(CompetitionCategory $category): self
    {
        $competition = $category->relationLoaded('competition') && $category->getRelation('competition') !== null
            ? $category->getRelation('competition')
            : Competition::withoutGlobalScope(OrganizationScope::class)->findOrFail($category->competition_id);

        return new self(
            maxParticipants: $category->max_participants ?? $competition?->max_participants,
            registrationStartsAt: $competition?->registration_starts_at,
            registrationEndsAt: $category->registration_ends_at ?? $competition?->registration_ends_at,
        );
    }

    public function isRegistrationOpen(CarbonInterface $now): bool
    {
        if ($this->registrationStartsAt !== null && $now->lt($this->registrationStartsAt)) {
            return false;
        }

        if ($this->registrationEndsAt !== null && $now->gt($this->registrationEndsAt)) {
            return false;
        }

        return true;
    }

    public function hasCapacity(int $currentConfirmedCount): bool
    {
        if ($this->maxParticipants === null) {
            return true;
        }

        return $currentConfirmedCount < $this->maxParticipants;
    }
}
