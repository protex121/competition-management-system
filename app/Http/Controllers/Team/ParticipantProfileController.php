<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\UpsertParticipantProfileRequest;
use App\Models\ParticipantProfile;
use App\Services\Team\UpsertParticipantProfileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantProfileController extends Controller
{
    public function edit(): Response
    {
        $user = request()->user();
        $profile = $user->participantProfile;

        if ($profile !== null) {
            $this->authorize('view', $profile);
        } else {
            $this->authorize('create', ParticipantProfile::class);
        }

        return Inertia::render('team/profile/Edit', [
            'profile' => $profile,
        ]);
    }

    public function update(
        UpsertParticipantProfileRequest $request,
        UpsertParticipantProfileService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $request->validated());

        return to_route('participant.profile.edit');
    }
}
