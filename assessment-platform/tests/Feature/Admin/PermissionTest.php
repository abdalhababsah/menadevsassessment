<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('permission catalog seeds the correct number of permissions', function () {
    $expectedCount = collect(PermissionSeeder::PERMISSIONS)->flatten()->count();

    expect(Permission::count())->toBe($expectedCount)
        ->and($expectedCount)->toBe(37);
});

test('super admin has all permissions via is_super_admin flag', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $allPermissions = Permission::pluck('name');

    $allPermissions->each(function (string $permission) use ($superAdmin) {
        expect($superAdmin->hasPermissionTo($permission))->toBeTrue();
    });
});

test('super admin passes Gate checks', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin);

    expect($superAdmin->can('quiz.view'))->toBeTrue()
        ->and($superAdmin->can('system.settings'))->toBeTrue()
        ->and($superAdmin->can('nonexistent.permission'))->toBeTrue();
});

test('regular user with no role has no permissions', function () {
    $user = User::factory()->create();

    $allPermissions = Permission::pluck('name');

    $allPermissions->each(function (string $permission) use ($user) {
        expect($user->hasPermissionTo($permission))->toBeFalse();
    });
});

test('role assignment grants correct permissions', function () {
    $role = Role::findOrCreate('Test Role', 'web');
    $role->syncPermissions(['quiz.view', 'quiz.create', 'quiz.edit']);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->hasPermissionTo('quiz.view'))->toBeTrue()
        ->and($user->hasPermissionTo('quiz.create'))->toBeTrue()
        ->and($user->hasPermissionTo('quiz.edit'))->toBeTrue()
        ->and($user->hasPermissionTo('quiz.delete'))->toBeFalse()
        ->and($user->hasPermissionTo('system.settings'))->toBeFalse();
});

test('all permission groups are represented in the catalog', function () {
    $expectedGroups = [
        'Quizzes',
        'Question Bank',
        'Invitations',
        'Candidates',
        'Results',
        'RLHF',
        'Coding',
        'Users & Roles',
        'System',
    ];

    expect(array_keys(PermissionSeeder::PERMISSIONS))->toBe($expectedGroups);
});
