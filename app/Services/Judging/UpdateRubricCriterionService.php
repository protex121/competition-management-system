<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Models\RubricCriterion;

class UpdateRubricCriterionService
{
    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     max_score: int,
     *     sort_order?: int|null,
     * }  $data
     */
    public function execute(RubricCriterion $criterion, array $data): RubricCriterion
    {
        $criterion->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'max_score' => $data['max_score'],
            'sort_order' => $data['sort_order'] ?? $criterion->sort_order,
        ]);

        $criterion->save();

        return $criterion;
    }
}
