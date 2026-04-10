<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\DeactivateUserAction;
use App\Actions\Users\InviteUserAction;
use App\Actions\Users\ReactivateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\InviteUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with('roles')
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                /** @var Carbon|null $lastLogin */
                $lastLogin = $user->last_login_at;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'is_super_admin' => $user->is_super_admin,
                    'last_login_at' => $lastLogin?->toDateTimeString(),
                    'roles' => $user->getRoleNames()->toArray(),
                ];
            });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(InviteUserRequest $request, InviteUserAction $action): RedirectResponse
    {
        $action->handle(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('roles', []),
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User invited successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'is_super_admin' => $user->is_super_admin,
                'roles' => $user->getRoleNames()->toArray(),
            ],
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle(
            $user,
            $request->validated('name'),
            $request->validated('roles', []),
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user, DeactivateUserAction $action): RedirectResponse
    {
        if ($user->is_super_admin) {
            abort(403, 'Super admins cannot be deactivated.');
        }

        if (! $this->user()->can('users.deactivate')) {
            abort(403);
        }

        $action->handle($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deactivated successfully.');
    }

    public function reactivate(User $user, ReactivateUserAction $action): RedirectResponse
    {
        if (! $this->user()->can('users.deactivate')) {
            abort(403);
        }

        $action->handle($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User reactivated successfully.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }
}
