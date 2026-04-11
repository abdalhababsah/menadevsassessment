<?php

return [
    /*
    |----------------------------------------------------------------------
    | AI Provider Configuration
    |----------------------------------------------------------------------
    |
    | Controls the backend AI providers used for RLHF response generation
    | and any other assistant features. Values are read from environment
    | variables so production, staging, and local can diverge cleanly.
    |
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-sonnet-4-6'),
        'request_timeout_seconds' => (int) env('ANTHROPIC_TIMEOUT', 60),

        /*
        | Concurrency cap applied via Redis::funnel() to keep us under the
        | provider's per-minute rate limit. Set to 1 for serialized requests
        | during local development.
        */
        'concurrency_cap' => (int) env('ANTHROPIC_CONCURRENCY_CAP', 40),
    ],

    /*
    | Global funnel name used by GenerateRlhfTurnResponseJob when acquiring
    | its Redis::funnel() lock. Kept here so the job and the config stay in
    | sync.
    */
    'funnels' => [
        'anthropic' => env('ANTHROPIC_FUNNEL_NAME', 'anthropic-api'),
    ],
];
