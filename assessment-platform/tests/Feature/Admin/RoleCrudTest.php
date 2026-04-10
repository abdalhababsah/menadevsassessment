<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('list roles', function () {
    test('super admin can list roles', function () {
        Role::findOrCreate('Test Role', 'web');

        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Roles/Index')
                ->has('roles')
            );
    });
});

describe('create role', function () {
    test('super admin can view create form', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.create'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Roles/Create')
                ->has('permissionGroups')
            );
    });

    test('super admin can create a role with permissions', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Content Manager',
                'permissions' => ['quiz.view', 'quiz.create', 'quiz.edit'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Content Manager', 'web');
        expect($role)->not->toBeNull();
        expect($role->permissions->pluck('name')->toArray())
            ->toContain('quiz.view', 'quiz.create', 'quiz.edit');
    });

    test('cannot create role with invalid permissions', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Bad Role',
                'permissions' => ['fake.permission'],
            ])
            ->assertSessionHasErrors('permissions.0');
    });

    test('cannot create role with duplicate name', function () {
        Role::findOrCreate('Existing Role', 'web');

        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Existing Role',
                'permissions' => ['quiz.view'],
            ])
            ->assertSessionHasErrors('name');
    });
});

describe('update role', function () {
    test('super admin can update a role', function () {
        $role = Role::findOrCreate('Editor', 'web');
        $role->syncPermissions(['quiz.view']);

        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Senior Editor',
                'permissions' => ['quiz.view', 'quiz.edit', 'quiz.publish'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        expect($role->name)->toBe('Senior Editor');
        expect($role->permissions->pluck('name')->toArray())
            ->toContain('quiz.view', 'quiz.edit', 'quiz.publish');
    });
});

describe('clone role', function () {
    test('super admin can clone a role', function () {
        $original = Role::findOrCreate('Template', 'web');
        $original->syncPermissions(['quiz.view', 'quiz.create']);

        $this->actingAs($this->admin)
            ->post(route('admin.roles.clone', $original), [
                'name' => 'Template Clone',
            ])
            ->assertRedirect(route('admin.roles.index'));

        $clone = Role::findByName('Template Clone', 'web');
        expect($clone)->not->toBeNull();
        expect($clone->permissions->pluck('name')->sort()->values()->toArray())
            ->toBe($original->permissions->pluck('name')->sort()->values()->toArray());
    });
});

describe('delete role with reassignment', function () {
    test('super admin can delete a role and reassign users', function () {
        $roleToDelete = Role::findOrCreate('Deprecated', 'web');
        $replacementRole = Role::findOrCreate('Replacement', 'web');

        $user = User::factory()->create();
        $user->assignRole($roleToDelete);

        $this->actingAs($this->admin)
            ->delete(route('admin.roles.destroy', $roleToDelete), [
                'replacement_role_id' => $replacementRole->id,
            ])
            ->assertRedirect(route('admin.roles.index'));

        expect(Role::where('name', 'Deprecated')->exists())->toBeFalse();
        expect($user->fresh()->hasRole('Replacement'))->toBeTrue();
    });

    test('cannot delete Super Admin role', function () {
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $replacement = Role::findOrCreate('Fallback', 'web');

        $this->actingAs($this->admin)
            ->delete(route('admin.roles.destroy', $superAdminRole), [
                'replacement_role_id' => $replacement->id,
            ])
            ->assertForbidden();
    });
});

describe('authorization', function () {
    test('non-authorized user cannot access roles', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    });

    test('user with roles.manage permission can access roles', function () {
        $role = Role::findOrCreate('Role Manager', 'web');
        $role->syncPermissions(['roles.manage']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertStatus(200);
    });
});

describe('audit log entries', function () {
    test('creating a role creates an audit log entry', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Audited Role',
                'permissions' => ['quiz.view'],
            ]);

        $log = AuditLog::where('action', 'role.created')->latest('id')->first();

        expect($log)->not->toBeNull()
            ->and($log->user_id)->toBe($this->admin->id)
            ->and($log->changes['name'])->toBe('Audited Role');
    });

    test('updating a role creates an audit log entry', function () {
        $role = Role::findOrCreate('Before Update', 'web');
        $role->syncPermissions(['quiz.view']);

        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'After Update',
                'permissions' => ['quiz.view', 'quiz.edit'],
            ]);

        $log = AuditLog::where('action', 'role.updated')->latest('id')->first();
        expect($log)->not->toBeNull();
    });

    test('deleting a role creates an audit log entry', function () {
        $role = Role::findOrCreate('To Delete', 'web');
        $replacement = Role::findOrCreate('Keep', 'web');

        $this->actingAs($this->admin)
            ->delete(route('admin.roles.destroy', $role), [
                'replacement_role_id' => $replacement->id,
            ]);

        $log = AuditLog::where('action', 'role.deleted')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['name'])->toBe('To Delete')
            ->and($log->changes['replacement_role'])->toBe('Keep');
    });

    test('cloning a role creates an audit log entry', function () {
        $original = Role::findOrCreate('Original', 'web');
        $original->syncPermissions(['quiz.view']);

        $this->actingAs($this->admin)
            ->post(route('admin.roles.clone', $original), [
                'name' => 'Cloned',
            ]);

        $log = AuditLog::where('action', 'role.cloned')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['source_role'])->toBe('Original');
    });
});
