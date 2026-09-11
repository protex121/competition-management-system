<?php

declare(strict_types=1);

namespace App\Services\Leaderboard;

use App\Enums\SubmissionStatus;
use App\Models\CompetitionCategory;
use App\Models\LeaderboardEntry;
use App\Models\Registration;
use App\Models\Score;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalculateCategoryLeaderboardService
{
    public function execute(CompetitionCategory $category): void
    {
        $registrationIds = Registration::withoutGlobalScopes()
            ->where('competition_category_id', $category->id)
            ->pluck('id');

        $submissions = Submission::withoutGlobalScopes()
            ->whereIn('registration_id', $registrationIds)
            ->where('status', SubmissionStatus::Finalized)
            ->get(['id', 'submitted_at']);

        if ($submissions->isEmpty()) {
            $this->replaceEntries($category, collect());

            return;
        }

        $judgeTotalsBySubmission = Score::withoutGlobalScopes()
            ->whereIn('submission_id', $submissions->pluck('id'))
            ->selectRaw('submission_id, judge_id, SUM(score) as judge_total')
            ->groupBy('submission_id', 'judge_id')
            ->get()
            ->groupBy('submission_id');

        $ranked = $submissions
            ->map(function (Submission $submission) use ($judgeTotalsBySubmission) {
                $judgeTotals = $judgeTotalsBySubmission->get($submission->id, collect());

                if ($judgeTotals->isEmpty()) {
                    return null;
                }

                return [
                    'submission_id' => $submission->id,
                    'submitted_at' => $submission->submitted_at,
                    'aggregate_score' => round($judgeTotals->avg('judge_total'), 2),
                    'judge_count' => $judgeTotals->count(),
                ];
            })
            ->filter()
            ->sort(function (array $a, array $b): int {
                return $b['aggregate_score'] <=> $a['aggregate_score']
                    ?: $a['submitted_at'] <=> $b['submitted_at'];
            })
            ->values();

        $this->replaceEntries($category, $ranked);
    }

    /**
     * @param  Collection<int, array{submission_id: int, submitted_at: mixed, aggregate_score: float, judge_count: int}>  $ranked
     */
    private function replaceEntries(CompetitionCategory $category, $ranked): void
    {
        DB::transaction(function () use ($category, $ranked): void {
            LeaderboardEntry::withoutGlobalScopes()
                ->where('competition_category_id', $category->id)
                ->delete();

            $now = now();

            $rows = $ranked->values()->map(fn (array $entry, int $index): array => [
                'competition_category_id' => $category->id,
                'submission_id' => $entry['submission_id'],
                'aggregate_score' => $entry['aggregate_score'],
                'judge_count' => $entry['judge_count'],
                'rank' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                LeaderboardEntry::withoutGlobalScopes()->insert($rows);
            }
        });
    }
}
