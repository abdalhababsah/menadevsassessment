<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

describe('login flow', function () {
    test('login page renders', function () {
        $this->get('/login')->assertStatus(200);
    });

    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    });

    test('user cannot login with wrong password', function () {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    });

    test('dashboard requires authentication', function () {
        $this->get('/dashboard')->assertRedirect('/login');
    });

    test('authenticated user can access dashboard', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200);
    });

    test('registration routes are disabled', function () {
        $this->get('/register')->assertStatus(404);
        $this->post('/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(404);
    });
});

describe('inactive user blocked', function () {
    test('inactive user is logged out when accessing dashboard', function () {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    });

    test('inactive user sees deactivation error', function () {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertStatus(200);
    });
});

describe('super admin permission check via shared props', function () {
    test('super admin has all permissions in shared props', function () {
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('auth.user.is_super_admin', true)
            ->has('auth.user.permissions')
            ->has('auth.user.roles')
        );
    });
});

describe('regular user permission check', function () {
    test('user with role has only assigned permissions in shared props', function () {
        $role = Role::findOrCreate('Test Role', 'web');
        $role->syncPermissions(['quiz.view', 'quiz.create']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('auth.user.is_super_admin', false)
            ->where('auth.user.permissions', ['quiz.view', 'quiz.create'])
            ->where('auth.user.roles', ['Test Role'])
        );
    });

    test('user with no role has empty permissions', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.permissions', [])
            ->where('auth.user.roles', [])
        );
    });
});
