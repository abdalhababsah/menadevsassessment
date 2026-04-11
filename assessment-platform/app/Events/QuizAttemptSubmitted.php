<?php

namespace App\Events;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizAttemptSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly QuizAttempt $attempt,
    ) {}
}
