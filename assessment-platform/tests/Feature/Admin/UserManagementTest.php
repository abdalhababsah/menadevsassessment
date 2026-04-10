<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
});

describe('invite flow end-to-end', function () {
    test('admin can view invite form', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertStatus(200);
    });

    test('admin can invite a new user with roles', function () {
        Notification::fake();

        $role = Role::findOrCreate('Reviewer', 'web');

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'roles' => ['Reviewer'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'jane@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->is_active)->toBeFalse()
            ->and($user->hasRole('Reviewer'))->toBeTrue();

        // Verification: invitation created
        expect($user->invitations)->toHaveCount(1);

        // Notification dispatched
        Notification::assertSentTo($user, UserInvitationNotification::class);
    });

    test('inviting a user creates audit log entry', function () {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Audit Test',
                'email' => 'audit@example.com',
                'roles' => [],
            ]);

        $log = AuditLog::where('action', 'user.invited')->latest('id')->first();
        expect($log)->not->toBeNull()
            ->and($log->changes['email'])->toBe('audit@example.com');
    });

    test('cannot invite with duplicate email', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Duplicate',
                'email' => 'taken@example.com',
                'roles' => [],
            ])
            ->assertSessionHasErrors('email');
    });
});

describe('invitation acceptance', function () {
    test('user can view invitation page with valid token', function () {
        $user = User::factory()->create(['is_active' => false]);
        $invitation = $user->invitations()->create([
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(7),
        ]);

        $this->get(route('invitations.show', $invitation->token))
            ->assertStatus(200);
    });

    test('user can set password and accept invitation', function () {
        $user = User::factory()->create(['is_active' => false]);
        $invitation = $user->invitations()->create([
            'token' => str_repeat('b', 64),
            'expires_at' => now()->addDays(7),
        ]);

        $this->post(route('invitations.store', $invitation->token), [
            'password' => 'newSecurePassword123',
            'password_confirmation' => 'newSecurePassword123',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $invitation->refresh();

        expect($user->is_active)->toBeTrue()
            ->and($invitation->consumed_at)->not->toBeNull()
            ->and($this->isAuthenticated())->toBeTrue();
    });

    test('expired invitation is rejected', function () {
        $user = User::factory()->create(['is_active' => false]);
        $invitation = $user->invitations()->create([
            'token' => str_repeat('c', 64),
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('invitations.show', $invitation->token))
            ->assertRedirect(route('login'));

        $this->post(route('invitations.store', $invitation->token), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        expect($user->fresh()->is_active)->toBeFalse();
    });

    test('consumed invitation cannot be reused', function () {
        $user = User::factory()->create(['is_active' => false]);
        $invitation = $user->invitations()->create([
            'token' => str_repeat('d', 64),
            'expires_at' => now()->addDays(7),
            'consumed_at' => now(),
        ]);

        $this->get(route('invitations.show', $invitation->token))
            ->assertRedirect(route('login'));
    });
});

describe('deactivation', function () {
    test('admin can deactivate a user', function () {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        expect($user->fresh()->is_active)->toBeFalse();
    });

    test('cannot deactivate a super admin', function () {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertForbidden();

        expect($superAdmin->fresh()->is_active)->toBeTrue();
    });

    test('deactivated user cannot log in', function () {
        $user = User::factory()->inactive()->create([
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Login may succeed but the active middleware on dashboard will log them out
        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertRedirect('/login');
    });

    test('deactivation creates audit log entry', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user));

        $log = AuditLog::where('action', 'user.deactivated')->latest('id')->first();
        expect($log)->not->toBeNull();
    });

    test('admin can reactivate a user', function () {
        $user = User::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.reactivate', $user));

        expect($user->fresh()->is_active)->toBeTrue();
    });
});

describe('update user', function () {
    test('admin can update user name and roles', function () {
        Role::findOrCreate('Editor', 'web');
        Role::findOrCreate('Reviewer', 'web');

        $user = User::factory()->create(['name' => 'Old Name']);
        $user->assignRole('Editor');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'New Name',
                'roles' => ['Reviewer'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        expect($user->name)->toBe('New Name')
            ->and($user->hasRole('Reviewer'))->toBeTrue()
            ->and($user->hasRole('Editor'))->toBeFalse();
    });

    test('updating a user creates audit log entry', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated',
                'roles' => [],
            ]);

        $log = AuditLog::where('action', 'user.updated')->latest('id')->first();
        expect($log)->not->toBeNull();
    });
});

describe('authorization', function () {
    test('user without users.view cannot access user list', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    });

    test('user with users.view permission can access list', function () {
        $role = Role::findOrCreate('Viewer', 'web');
        $role->syncPermissions(['users.view']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertStatus(200);
    });
});
