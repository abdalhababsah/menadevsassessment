<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'password' => null,
            'email_verified_at' => null,
            'is_guest' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    public function registered(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => static::$password ??= Hash::make('password'),
            'is_guest' => false,
            'email_verified_at' => now(),
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'is_guest' => true,
        ]);
    }
}
