<?php

declare(strict_types=1);

namespace App\Http\Controllers\Judging;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judging\SubmitScoreRequest;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Score;
use App\Models\Submission;
use App\Services\Judging\ListJudgeQueueService;
use App\Services\Judging\SubmitScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoreController extends Controller
{
    public function queue(Request $request, ListJudgeQueueService $service): Response
    {
        $user = $request->user();

        abort_unless($user->isJudge() || $user->isSuperAdmin(), 403);

        $submissions = $service->execute($user);

        return Inertia::render('judging/Queue', [
            'submissions' => $submissions->map(fn (Submission $submission) => [
                'id' => $submission->id,
                'title' => $submission->title,
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'registrant' => $submission->registration->team?->name ?? $submission->registration->user?->name,
                'type' => $submission->registration->isTeam() ? 'team' : 'individual',
                'competition' => [
                    'id' => $submission->registration->category->competition->id,
                    'name' => $submission->registration->category->competition->name,
                ],
                'category' => [
                    'id' => $submission->registration->category->id,
                    'name' => $submission->registration->category->name,
                ],
                'has_scored' => $submission->scores()->where('judge_id', $user->id)->exists(),
            ])->values(),
        ]);
    }

    public function edit(Request $request, Submission $submission): Response
    {
        $this->authorize('manage', [Score::class, $submission]);

        $user = $request->user();
        $competition = $submission->registration->category->competition;

        $rubric = Rubric::withoutGlobalScopes()->where('competition_id', $competition->id)->first();
        $criteria = $rubric?->criteria()->get() ?? collect();

        $existingScores = Score::withoutGlobalScopes()
            ->where('submission_id', $submission->id)
            ->where('judge_id', $user->id)
            ->get()
            ->keyBy('rubric_criterion_id');

        return Inertia::render('judging/ScoreSubmission', [
            'submission' => [
                'id' => $submission->id,
                'title' => $submission->title,
                'description' => $submission->description,
                'project_url' => $submission->project_url,
                'file_original_name' => $submission->file_original_name,
                'has_file' => $submission->hasFile(),
                'registrant' => $submission->registration->team?->name ?? $submission->registration->user?->name,
                'type' => $submission->registration->isTeam() ? 'team' : 'individual',
            ],
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
            'criteria' => $criteria->map(fn (RubricCriterion $criterion) => [
                'id' => $criterion->id,
                'name' => $criterion->name,
                'description' => $criterion->description,
                'max_score' => $criterion->max_score,
                'score' => $existingScores->get($criterion->id)?->score,
                'comment' => $existingScores->get($criterion->id)?->comment,
            ])->values(),
        ]);
    }

    public function update(
        SubmitScoreRequest $request,
        Submission $submission,
        SubmitScoreService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $submission, $request->validated('entries'));

        return to_route('judging.queue.index')->with('success', 'Score saved.');
    }
}
