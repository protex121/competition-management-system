<?php

declare(strict_types=1);

namespace App\Services\Judging;

use App\Enums\CompetitionJudgeStatus;
use App\Enums\SubmissionStatus;
use App\Models\CompetitionJudge;
use App\Models\Score;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Collection;

class ListJudgeQueueService
{
    /**
     * @return Collection<int, Submission>
     */
    public function execute(User $judge): Collection
    {
        $competitionIds = CompetitionJudge::withoutGlobalScopes()
            ->where('user_id', $judge->id)
            ->where('status', CompetitionJudgeStatus::Active)
            ->pluck('competition_id');

        if ($competitionIds->isEmpty()) {
            return collect();
        }

        return Submission::withoutGlobalScopes()
            ->where('status', SubmissionStatus::Finalized)
            ->whereHas('registration.category', function ($query) use ($competitionIds): void {
                $query->whereIn('competition_id', $competitionIds);
            })
            ->with(['registration.user', 'registration.team', 'registration.category.competition'])
            ->orderByDesc('submitted_at')
            ->get()
            ->filter(fn (Submission $submission) => $judge->can('manage', [Score::class, $submission]))
            ->values();
    }
}
