<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizInvitation>
 */
class QuizInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'max_uses' => null,
            'uses_count' => 0,
            'expires_at' => null,
            'email_domain_restriction' => null,
            'created_by' => User::factory(),
            'revoked_at' => null,
        ];
    }

    public function limited(int $maxUses = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => $maxUses,
        ]);
    }

    public function expiring(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 5,
            'uses_count' => 5,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    public function domainRestricted(string $domain = 'example.com'): static
    {
        return $this->state(fn (array $attributes) => [
            'email_domain_restriction' => $domain,
        ]);
    }
}
