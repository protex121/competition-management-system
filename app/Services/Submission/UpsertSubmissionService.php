<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\SubmissionStatus;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class UpsertSubmissionService
{
    /**
     * @param  array{title: string, description?: string|null, project_url?: string|null}  $data
     */
    public function execute(User $actor, Registration $registration, array $data): Submission
    {
        if (! $actor->can('manage', [Submission::class, $registration])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $existing = Submission::withoutGlobalScopes()
            ->where('registration_id', $registration->id)
            ->first();

        if ($existing !== null && $existing->isFinalized()) {
            throw ValidationException::withMessages([
                'submission' => ['This submission has already been finalized and cannot be edited.'],
            ]);
        }

        return Submission::withoutGlobalScopes()->updateOrCreate(
            ['registration_id' => $registration->id],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'project_url' => $data['project_url'] ?? null,
                'status' => SubmissionStatus::Draft,
            ],
        );
    }
}
