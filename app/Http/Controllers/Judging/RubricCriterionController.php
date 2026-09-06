<?php

declare(strict_types=1);

namespace App\Http\Controllers\Judging;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judging\StoreRubricCriterionRequest;
use App\Http\Requests\Judging\UpdateRubricCriterionRequest;
use App\Models\Competition;
use App\Models\RubricCriterion;
use App\Services\Judging\CreateRubricCriterionService;
use App\Services\Judging\DeleteRubricCriterionService;
use App\Services\Judging\UpdateRubricCriterionService;
use Illuminate\Http\RedirectResponse;

class RubricCriterionController extends Controller
{
    public function store(
        StoreRubricCriterionRequest $request,
        Competition $competition,
        CreateRubricCriterionService $service,
    ): RedirectResponse {
        $service->execute($competition, $request->validated());

        return back()->with('success', 'Criterion added.');
    }

    public function update(
        UpdateRubricCriterionRequest $request,
        RubricCriterion $criterion,
        UpdateRubricCriterionService $service,
    ): RedirectResponse {
        $service->execute($criterion, $request->validated());

        return back()->with('success', 'Criterion updated.');
    }

    public function destroy(RubricCriterion $criterion, DeleteRubricCriterionService $service): RedirectResponse
    {
        $this->authorize('delete', $criterion);

        $service->execute($criterion);

        return back()->with('success', 'Criterion deleted.');
    }
}
