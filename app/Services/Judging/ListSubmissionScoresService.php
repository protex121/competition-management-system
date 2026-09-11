<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Models\Score;
use App\Models\Submission;
use Illuminate\Support\Collection;

class ListSubmissionScoresService
{
    /**
     * @param  Collection<int, Submission>  $submissions
     * @return array<int, int> submission_id => distinct judge count
     */
    public function countsFor(Collection $submissions): array
    {
        if ($submissions->isEmpty()) {
            return [];
        }

        return Score::withoutGlobalScopes()
            ->whereIn('submission_id', $submissions->pluck('id'))
            ->selectRaw('submission_id, COUNT(DISTINCT judge_id) as judge_count')
            ->groupBy('submission_id')
            ->pluck('judge_count', 'submission_id')
            ->all();
    }
}
