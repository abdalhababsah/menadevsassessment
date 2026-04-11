<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

describe('admin navigation links', function () {
    test('candidates link returns 200 for user with permission', function () {
        $role = Role::findOrCreate('Candidates Admin', 'web');
        $role->syncPermissions(['candidate.view']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin/candidates')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Admin/Candidates/Index'));
    });

    test('candidates link returns 403 without permission', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/candidates')
            ->assertStatus(403);
    });

    test('settings link returns 200 for user with permission', function () {
        $role = Role::findOrCreate('Settings Admin', 'web');
        $role->syncPermissions(['system.settings']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Admin/Settings/Index'));
    });

    test('settings link returns 403 without permission', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertStatus(403);
    });

    test('super admin can access both links', function () {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertStatus(200);

        $this->actingAs($user)
            ->get('/admin/candidates')
            ->assertStatus(200);
    });
});
