<?php

namespace App\Services\AiProviders\Anthropic;

use App\Contracts\AiProviders\AiResponseGenerator;
use App\Contracts\AiProviders\GeneratedResponse;
use App\Exceptions\NotImplementedException;

final class AnthropicResponseGenerator implements AiResponseGenerator
{
    public function generate(array $messages, string $model, array $params = []): GeneratedResponse
    {
        // TODO: Implement Anthropic API integration
        throw new NotImplementedException('Anthropic AI response generator is not yet implemented.');
    }
}
