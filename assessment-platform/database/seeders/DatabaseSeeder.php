<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * `php artisan migrate:fresh --seed` should produce a usable starting
     * state: permission catalog, role templates, a super admin user, and a
     * small demo quiz covering every question type.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            QuizAuthorRoleSeeder::class,
            ReviewerRoleSeeder::class,
            ProctorRoleSeeder::class,
            AuditorRoleSeeder::class,
            DemoQuizSeeder::class,
        ]);
    }
}
