<?php

declare(strict_types=1);

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\StoreIndividualRegistrationRequest;
use App\Http\Requests\Registration\StoreTeamRegistrationRequest;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Team;
use App\Services\Registration\ListParticipantRegistrationsService;
use App\Services\Registration\ListRegistrationsService;
use App\Services\Registration\RegisterParticipantService;
use App\Services\Registration\RegisterTeamService;
use App\Services\Registration\WithdrawRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function index(Request $request, ListParticipantRegistrationsService $service): Response
    {
        $registrations = $service->execute($request->user());

        return Inertia::render('registration/registrations/Index', [
            'registrations' => $registrations->map(fn (Registration $registration) => $this->present($registration))->values(),
        ]);
    }

    public function storeIndividual(
        StoreIndividualRegistrationRequest $request,
        Competition $competition,
        RegisterParticipantService $service,
    ): RedirectResponse {
        $category = CompetitionCategory::query()->findOrFail($request->validated('competition_category_id'));

        $service->execute($request->user(), $category);

        return to_route('registrations.index');
    }

    public function storeTeam(
        StoreTeamRegistrationRequest $request,
        Team $team,
        RegisterTeamService $service,
    ): RedirectResponse {
        $category = CompetitionCategory::query()->findOrFail($request->validated('competition_category_id'));

        $service->execute($request->user(), $team, $category);

        return to_route('teams.show', $team);
    }

    public function withdraw(
        Request $request,
        Registration $registration,
        WithdrawRegistrationService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $registration);

        return to_route('registrations.index');
    }

    public function review(
        Request $request,
        Competition $competition,
        CompetitionCategory $category,
        ListRegistrationsService $service,
    ): Response {
        $this->authorize('viewAny', [Registration::class, $competition]);

        $registrations = $service->execute($category);

        return Inertia::render('registration/registrations/Review', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'registrations' => $registrations->map(fn (Registration $registration) => $this->present($registration))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Registration $registration): array
    {
        return [
            'id' => $registration->id,
            'status' => $registration->status->value,
            'created_at' => $registration->created_at->toISOString(),
            'withdrawn_at' => $registration->withdrawn_at?->toISOString(),
            'category' => [
                'id' => $registration->category->id,
                'name' => $registration->category->name,
                'competition' => [
                    'id' => $registration->category->competition->id,
                    'name' => $registration->category->competition->name,
                ],
            ],
            'user' => $registration->user ? [
                'id' => $registration->user->id,
                'name' => $registration->user->name,
            ] : null,
            'team' => $registration->team ? [
                'id' => $registration->team->id,
                'name' => $registration->team->name,
            ] : null,
        ];
    }
}
