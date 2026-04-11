<?php

namespace App\Services\AiProviders\Anthropic;

use App\Contracts\AiProviders\AiResponseGenerator;
use App\Contracts\AiProviders\GeneratedResponse;
use App\Exceptions\AiRateLimitException;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

final class AnthropicResponseGenerator implements AiResponseGenerator
{
    public function __construct(
        private HttpFactory $http,
    ) {}

    public function generate(array $messages, string $model, array $params = []): GeneratedResponse
    {
        $apiKey = (string) config('services.anthropic.key', '');

        if ($apiKey === '') {
            throw new RuntimeException('The Anthropic API key is not configured.');
        }

        $systemMessages = collect($messages)
            ->where('role', 'system')
            ->pluck('content')
            ->filter()
            ->implode("\n\n");

        $conversationMessages = collect($messages)
            ->reject(fn (array $message) => $message['role'] === 'system')
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => $message['content'],
            ])
            ->values()
            ->all();

        $payload = array_merge([
            'model' => $model,
            'max_tokens' => (int) ($params['max_tokens'] ?? config('services.anthropic.max_tokens', 2048)),
            'messages' => $conversationMessages,
        ], collect($params)->except(['max_tokens'])->all());

        if ($systemMessages !== '') {
            $payload['system'] = $systemMessages;
        }

        $response = $this->http
            ->baseUrl((string) config('services.anthropic.base_url', 'https://api.anthropic.com'))
            ->timeout((int) config('services.anthropic.timeout', 90))
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => (string) config('services.anthropic.version', '2023-06-01'),
            ])
            ->post('/v1/messages', $payload);

        if ($response->status() === 429) {
            throw new AiRateLimitException('Anthropic rate limit exceeded.');
        }

        $response->throw();

        $data = $response->json();
        $content = collect($data['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n\n");

        return new GeneratedResponse(
            content: $content,
            model: (string) ($data['model'] ?? $model),
            inputTokens: (int) ($data['usage']['input_tokens'] ?? 0),
            outputTokens: (int) ($data['usage']['output_tokens'] ?? 0),
            stopReason: $data['stop_reason'] ?? null,
        );
    }
}
