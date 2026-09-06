<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\SubmissionStatus;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\User;
use App\Services\Registration\EffectiveCategoryConfig;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSubmissionService
{
    public function execute(User $actor, Submission $submission): Submission
    {
        if (! $actor->can('finalize', $submission)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $registration = $this->resolveRegistration($submission);
        $category = $this->resolveCategory($registration);
        $config = EffectiveCategoryConfig::for($category);

        if (! $config->isSubmissionOpen(now())) {
            throw ValidationException::withMessages([
                'submission' => ['The submission deadline has passed.'],
            ]);
        }

        if ($submission->description === null && $submission->project_url === null && ! $submission->hasFile()) {
            throw ValidationException::withMessages([
                'submission' => ['Add a description, link, or file before finalizing.'],
            ]);
        }

        return DB::transaction(function () use ($submission): Submission {
            $submission->update([
                'status' => SubmissionStatus::Finalized,
                'submitted_at' => now(),
            ]);

            return $submission->refresh();
        });
    }

    private function resolveRegistration(Submission $submission): Registration
    {
        return $submission->relationLoaded('registration') && $submission->getRelation('registration') !== null
            ? $submission->getRelation('registration')
            : Registration::withoutGlobalScopes()->findOrFail($submission->registration_id);
    }

    private function resolveCategory(Registration $registration): CompetitionCategory
    {
        return $registration->relationLoaded('category') && $registration->getRelation('category') !== null
            ? $registration->getRelation('category')
            : CompetitionCategory::query()->findOrFail($registration->competition_category_id);
    }
}
