<?php

declare(strict_types=1);

namespace App\Http\Controllers\Competition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\ActivateCategoryRequest;
use App\Http\Requests\Competition\DisableCategoryRequest;
use App\Http\Requests\Competition\StoreCategoryRequest;
use App\Http\Requests\Competition\UpdateCategoryRequest;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Services\Competition\ActivateCategoryService;
use App\Services\Competition\CreateCategoryService;
use App\Services\Competition\DeleteCategoryService;
use App\Services\Competition\DisableCategoryService;
use App\Services\Competition\UpdateCategoryService;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function store(
        StoreCategoryRequest $request,
        Competition $competition,
        CreateCategoryService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $competition, $request->validated());

        return back()->with('success', 'Category created.');
    }

    public function update(
        UpdateCategoryRequest $request,
        Competition $competition,
        CompetitionCategory $category,
        UpdateCategoryService $service,
    ): RedirectResponse {
        $service->execute($category, $request->validated());

        return back()->with('success', 'Category updated.');
    }

    public function destroy(
        Competition $competition,
        CompetitionCategory $category,
        DeleteCategoryService $service,
    ): RedirectResponse {
        $this->authorize('delete', $category);

        $service->execute($category);

        return back()->with('success', 'Category deleted.');
    }

    public function activate(
        ActivateCategoryRequest $request,
        Competition $competition,
        CompetitionCategory $category,
        ActivateCategoryService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $category);

        return back()->with('success', 'Category activated.');
    }

    public function disable(
        DisableCategoryRequest $request,
        Competition $competition,
        CompetitionCategory $category,
        DisableCategoryService $service,
    ): RedirectResponse {
        $service->execute($request->user(), $category);

        return back()->with('success', 'Category disabled.');
    }
}
