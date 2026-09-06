<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Models\Competition;
use App\Models\Rubric;
use App\Models\RubricCriterion;

class CreateRubricCriterionService
{
    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     max_score: int,
     *     sort_order?: int|null,
     * }  $data
     */
    public function execute(Competition $competition, array $data): RubricCriterion
    {
        $rubric = Rubric::withoutGlobalScopes()->firstOrCreate(['competition_id' => $competition->id]);

        $sortOrder = $data['sort_order'] ?? ((int) $rubric->criteria()->max('sort_order')) + 1;

        return RubricCriterion::query()->create([
            'rubric_id' => $rubric->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'max_score' => $data['max_score'],
            'sort_order' => $sortOrder,
        ]);
    }
}
