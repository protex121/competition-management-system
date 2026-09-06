<?php

declare(strict_types=1);

use App\Http\Controllers\Submission\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('registrations/{registration}/submission', [SubmissionController::class, 'edit'])
        ->name('registrations.submission.edit');
    Route::put('registrations/{registration}/submission', [SubmissionController::class, 'update'])
        ->name('registrations.submission.update');

    Route::post('submissions/{submission}/file', [SubmissionController::class, 'storeFile'])
        ->name('submissions.file.store');
    Route::get('submissions/{submission}/file', [SubmissionController::class, 'downloadFile'])
        ->name('submissions.file.download');
    Route::post('submissions/{submission}/finalize', [SubmissionController::class, 'finalize'])
        ->name('submissions.finalize');
});

Route::middleware(['auth', 'verified', 'active', 'organizer'])->group(function () {
    Route::scopeBindings()->group(function () {
        Route::get('competitions/{competition}/categories/{category}/submissions', [SubmissionController::class, 'review'])
            ->name('competitions.categories.submissions.index');
    });
});
