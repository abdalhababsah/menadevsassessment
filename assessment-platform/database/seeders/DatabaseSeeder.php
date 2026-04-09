<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
        ]);

        // Role-template seeders — run individually via:
        //   php artisan db:seed --class=QuizAuthorRoleSeeder
        //   php artisan db:seed --class=ReviewerRoleSeeder
        //   php artisan db:seed --class=ProctorRoleSeeder
        //   php artisan db:seed --class=AuditorRoleSeeder
    }
}
