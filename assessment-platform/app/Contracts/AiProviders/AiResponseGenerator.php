<?php

namespace App\Contracts\AiProviders;

interface AiResponseGenerator
{
    /**
     * Generate a response from the AI provider.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $params
     */
    public function generate(array $messages, string $model, array $params = []): GeneratedResponse;
}
