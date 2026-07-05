<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Services\Identity\UpdatePasswordService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Password', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(UpdatePasswordRequest $request, UpdatePasswordService $service): RedirectResponse
    {
        $service->execute($request->user(), $request->validated());

        return back();
    }
}
