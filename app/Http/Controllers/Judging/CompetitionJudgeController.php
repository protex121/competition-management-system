<?php

declare(strict_types=1);

namespace App\Http\Controllers\Judging;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judging\AssignJudgeRequest;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Services\Judging\AssignJudgeService;
use App\Services\Judging\RemoveJudgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompetitionJudgeController extends Controller
{
    public function store(
        AssignJudgeRequest $request,
        Competition $competition,
        AssignJudgeService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $competition, $request->validated());

        return to_route('competitions.edit', $competition);
    }

    public function destroy(
        Request $request,
        Competition $competition,
        CompetitionJudge $judge,
        RemoveJudgeService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $competition, $judge);

        return to_route('competitions.edit', $competition);
    }
}
