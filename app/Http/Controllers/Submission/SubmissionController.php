<?php

declare(strict_types=1);

namespace App\Http\Controllers\Submission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Submission\UploadSubmissionFileRequest;
use App\Http\Requests\Submission\UpsertSubmissionRequest;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Submission;
use App\Services\Judging\ListSubmissionScoresService;
use App\Services\Submission\FinalizeSubmissionService;
use App\Services\Submission\ListSubmissionsService;
use App\Services\Submission\UploadSubmissionFileService;
use App\Services\Submission\UpsertSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function edit(Request $request, Registration $registration): Response
    {
        $this->authorize('manage', [Submission::class, $registration]);

        $submission = Submission::withoutGlobalScopes()
            ->where('registration_id', $registration->id)
            ->first();

        return Inertia::render('submission/Edit', [
            'registration' => [
                'id' => $registration->id,
                'category' => [
                    'id' => $registration->category->id,
                    'name' => $registration->category->name,
                    'competition' => [
                        'id' => $registration->category->competition->id,
                        'name' => $registration->category->competition->name,
                    ],
                ],
            ],
            'submission' => $submission ? $this->present($request, $submission) : null,
        ]);
    }

    public function update(
        UpsertSubmissionRequest $request,
        Registration $registration,
        UpsertSubmissionService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $registration, $request->validated());

        return to_route('registrations.submission.edit', $registration);
    }

    public function storeFile(
        UploadSubmissionFileRequest $request,
        Submission $submission,
        UploadSubmissionFileService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $submission, $request->file('file'));

        return back();
    }

    public function downloadFile(Request $request, Submission $submission): StreamedResponse
    {
        $this->authorize('view', $submission);

        abort_unless($submission->hasFile(), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->file_original_name);
    }

    public function finalize(
        Request $request,
        Submission $submission,
        FinalizeSubmissionService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $submission);

        return to_route('registrations.submission.edit', $submission->registration_id);
    }

    public function review(
        Request $request,
        Competition $competition,
        CompetitionCategory $category,
        ListSubmissionsService $service,
        ListSubmissionScoresService $scoresService,
    ): Response {
        $this->authorize('viewAny', [Submission::class, $competition]);

        $submissions = $service->execute($category);
        $scoreCounts = $scoresService->countsFor($submissions);

        return Inertia::render('submission/Review', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'submissions' => $submissions
                ->map(fn (Submission $submission) => $this->present($request, $submission, includeRegistrant: true, scoreCount: $scoreCounts[$submission->id] ?? 0))
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Request $request, Submission $submission, bool $includeRegistrant = false, ?int $scoreCount = null): array
    {
        $user = $request->user();

        $data = [
            'id' => $submission->id,
            'title' => $submission->title,
            'description' => $submission->description,
            'project_url' => $submission->project_url,
            'file_original_name' => $submission->file_original_name,
            'has_file' => $submission->hasFile(),
            'status' => $submission->status->value,
            'submitted_at' => $submission->submitted_at?->toISOString(),
            'can' => [
                'update' => $user->can('update', $submission),
                'finalize' => $user->can('finalize', $submission),
            ],
        ];

        if ($includeRegistrant) {
            $registration = $submission->registration;
            $data['registrant'] = $registration->team?->name ?? $registration->user?->name;
            $data['type'] = $registration->isTeam() ? 'team' : 'individual';
        }

        if ($scoreCount !== null) {
            $data['score_count'] = $scoreCount;
        }

        return $data;
    }
}
