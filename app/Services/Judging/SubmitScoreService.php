<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Rubric;
use App\Models\Score;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitScoreService
{
    /**
     * @param  array<int, array{rubric_criterion_id: int, score: int, comment?: string|null}>  $entries
     * @return Collection<int, Score>
     */
    public function execute(User $judge, Submission $submission, array $entries): Collection
    {
        if (! $judge->can('manage', [Score::class, $submission])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $registration = $this->resolveRegistration($submission);
        $category = $this->resolveCategory($registration);
        $rubric = Rubric::withoutGlobalScopes()->where('competition_id', $category->competition_id)->first();
        $criteria = $rubric?->criteria()->get()->keyBy('id') ?? collect();

        if ($criteria->isEmpty()) {
            throw ValidationException::withMessages([
                'score' => ['This competition has no rubric criteria to score against.'],
            ]);
        }

        $entriesByCriterion = collect($entries)->keyBy('rubric_criterion_id');

        if ($entriesByCriterion->keys()->sort()->values()->all() !== $criteria->keys()->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'score' => ['Every criterion must be scored.'],
            ]);
        }

        foreach ($entriesByCriterion as $criterionId => $entry) {
            $criterion = $criteria->get($criterionId);

            if ($entry['score'] < 0 || $entry['score'] > $criterion->max_score) {
                throw ValidationException::withMessages([
                    'score' => ["Score for \"{$criterion->name}\" must be between 0 and {$criterion->max_score}."],
                ]);
            }
        }

        return DB::transaction(function () use ($submission, $judge, $entriesByCriterion): Collection {
            return $entriesByCriterion->map(function (array $entry) use ($submission, $judge): Score {
                return Score::withoutGlobalScopes()->updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'rubric_criterion_id' => $entry['rubric_criterion_id'],
                        'judge_id' => $judge->id,
                    ],
                    [
                        'score' => $entry['score'],
                        'comment' => $entry['comment'] ?? null,
                    ],
                );
            })->values();
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
