<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\StoreUserRequest;
use App\Http\Requests\Identity\UpdateUserRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Identity\CreateUserService;
use App\Services\Identity\DeactivateUserService;
use App\Services\Identity\DeleteUserService;
use App\Services\Identity\ListUsersService;
use App\Services\Identity\ReactivateUserService;
use App\Services\Identity\UpdateUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, ListUsersService $service): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('identity/users/Index', [
            'users' => $service->execute($request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('identity/users/Create', [
            'roles' => $this->roleOptions(),
            'organizations' => $request->user()->isSuperAdmin()
                ? Organization::query()->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserService $service): RedirectResponse
    {
        $user = $service->execute($request->user(), $request->validated());

        return to_route('users.edit', $user);
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('organization');

        return Inertia::render('identity/users/Edit', [
            'user' => $user,
            'roles' => $this->roleOptions(),
            'can' => [
                'deactivate' => $request->user()->can('deactivate', $user),
                'reactivate' => $request->user()->can('reactivate', $user),
                'delete' => $request->user()->can('delete', $user),
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserService $service): RedirectResponse
    {
        $service->execute($user, $request->validated());

        return to_route('users.edit', $user);
    }

    public function destroy(User $user, DeleteUserService $service): RedirectResponse
    {
        $this->authorize('delete', $user);

        $service->execute($user);

        return to_route('users.index');
    }

    public function deactivate(User $user, DeactivateUserService $service): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $service->execute($user);

        return to_route('users.edit', $user);
    }

    public function reactivate(User $user, ReactivateUserService $service): RedirectResponse
    {
        $this->authorize('reactivate', $user);

        $service->execute($user);

        return to_route('users.edit', $user);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            UserRole::assignable(),
        );
    }
}
