<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $role = Role::findOrCreate('Super Admin', 'web');
        $role->syncPermissions(Permission::all());

        $user = User::firstOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
                'is_super_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole($role);

        $this->command->info("Super Admin user created: {$user->email}");
    }
}
