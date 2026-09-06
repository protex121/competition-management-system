<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Models\RubricCriterion;
use App\Models\Score;
use Illuminate\Validation\ValidationException;

class DeleteRubricCriterionService
{
    public function execute(RubricCriterion $criterion): void
    {
        $hasScores = Score::withoutGlobalScopes()
            ->where('rubric_criterion_id', $criterion->id)
            ->exists();

        if ($hasScores) {
            throw ValidationException::withMessages([
                'criterion' => ['This criterion already has scores recorded and cannot be deleted.'],
            ]);
        }

        $criterion->delete();
    }
}
