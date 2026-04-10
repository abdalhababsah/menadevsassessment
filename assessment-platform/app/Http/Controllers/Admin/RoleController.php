<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Roles\CloneRoleAction;
use App\Actions\Roles\CreateRoleAction;
use App\Actions\Roles\DeleteRoleAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('users', 'permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'created_at' => $role->created_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Create', [
            'permissionGroups' => PermissionSeeder::PERMISSIONS,
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): RedirectResponse
    {
        $action->handle(
            $request->validated('name'),
            $request->validated('permissions'),
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ],
            'permissionGroups' => PermissionSeeder::PERMISSIONS,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        $action->handle(
            $role,
            $request->validated('name'),
            $request->validated('permissions'),
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role, DeleteRoleAction $action): RedirectResponse
    {
        if ($role->name === 'Super Admin') {
            abort(403, 'The Super Admin role cannot be deleted.');
        }

        $this->authorize('delete', $role);

        $request->validate([
            'replacement_role_id' => ['required', 'exists:roles,id'],
        ]);

        $replacementRole = Role::findOrFail($request->input('replacement_role_id'));

        $action->handle($role, $replacementRole);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted and users reassigned.');
    }

    public function clone(Request $request, Role $role, CloneRoleAction $action): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $action->handle($role, $request->input('name'));

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role cloned successfully.');
    }
}
