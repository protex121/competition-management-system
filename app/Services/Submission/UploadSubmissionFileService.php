<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadSubmissionFileService
{
    private const DISK = 'local';

    public function execute(User $actor, Submission $submission, UploadedFile $file): Submission
    {
        if (! $actor->can('update', $submission)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $previousPath = $submission->file_path;

        $path = $file->store("submissions/{$submission->registration_id}", self::DISK);

        $submission->update([
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
        ]);

        $this->deletePrevious($previousPath);

        return $submission->refresh();
    }

    private function deletePrevious(?string $path): void
    {
        if ($path !== null && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
