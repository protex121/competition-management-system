<?php

declare(strict_types=1);

namespace App\Http\Controllers\Competition;

use App\Exceptions\Competition\InvalidCompetitionStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\ActivateCompetitionRequest;
use App\Http\Requests\Competition\CloseCompetitionRequest;
use App\Http\Requests\Competition\PublishCompetitionRequest;
use App\Http\Requests\Competition\StoreCompetitionRequest;
use App\Http\Requests\Competition\UpdateCompetitionRequest;
use App\Models\Competition;
use App\Models\Organization;
use App\Services\Competition\ActivateCompetitionService;
use App\Services\Competition\CloseCompetitionService;
use App\Services\Competition\CreateCompetitionService;
use App\Services\Competition\DeleteCompetitionService;
use App\Services\Competition\ListCompetitionsService;
use App\Services\Competition\PublishCompetitionService;
use App\Services\Competition\UpdateCompetitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function index(Request $request, ListCompetitionsService $service): Response
    {
        $this->authorize('viewAny', Competition::class);

        return Inertia::render('competition/competitions/Index', [
            'competitions' => $service->execute($request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Competition::class);

        return Inertia::render('competition/competitions/Create', [
            'organizations' => $request->user()->isSuperAdmin()
                ? Organization::query()->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    public function store(StoreCompetitionRequest $request, CreateCompetitionService $service): RedirectResponse
    {
        $competition = $service->execute($request->user(), $request->validated());

        return to_route('competitions.edit', $competition);
    }

    public function edit(Request $request, Competition $competition): Response
    {
        $this->authorize('view', $competition);

        $competition->load(['organization', 'categories']);

        return Inertia::render('competition/competitions/Edit', [
            'competition' => $competition,
            'can' => [
                'update' => $request->user()->can('update', $competition),
                'delete' => $request->user()->can('delete', $competition),
                'publish' => $request->user()->can('publish', $competition),
                'activate' => $request->user()->can('activate', $competition),
                'close' => $request->user()->can('close', $competition),
            ],
        ]);
    }

    public function update(UpdateCompetitionRequest $request, Competition $competition, UpdateCompetitionService $service): RedirectResponse
    {
        $service->execute($competition, $request->validated());

        return to_route('competitions.edit', $competition);
    }

    public function destroy(Competition $competition, DeleteCompetitionService $service): RedirectResponse
    {
        $this->authorize('delete', $competition);

        $service->execute($competition);

        return to_route('competitions.index');
    }

    public function publish(
        PublishCompetitionRequest $request,
        Competition $competition,
        PublishCompetitionService $service,
    ): RedirectResponse {
        return $this->runLifecycle(
            fn (): Competition => $service->execute($request->user(), $competition),
            'Competition published.',
        );
    }

    public function activate(
        ActivateCompetitionRequest $request,
        Competition $competition,
        ActivateCompetitionService $service,
    ): RedirectResponse {
        return $this->runLifecycle(
            fn (): Competition => $service->execute($request->user(), $competition),
            'Competition activated.',
        );
    }

    public function close(
        CloseCompetitionRequest $request,
        Competition $competition,
        CloseCompetitionService $service,
    ): RedirectResponse {
        return $this->runLifecycle(
            fn (): Competition => $service->execute($request->user(), $competition),
            'Competition closed.',
        );
    }

    /**
     * @param  callable(): Competition  $action
     */
    private function runLifecycle(callable $action, string $message): RedirectResponse
    {
        try {
            $competition = $action();
        } catch (InvalidCompetitionStatusTransitionException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return to_route('competitions.edit', $competition)->with('success', $message);
    }
}
