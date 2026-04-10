<?php

namespace App\Actions\Quizzes;

use App\Models\Quiz;
use App\Services\AuditLogger;

final class UpdateQuizSettingsAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $settings
     */
    public function handle(Quiz $quiz, array $settings): Quiz
    {
        $previous = $quiz->only(array_keys($settings));

        $quiz->update($settings);

        $this->audit->log('quiz.settings_updated', $quiz, [
            'changes' => array_keys($settings),
            'previous' => $previous,
        ]);

        return $quiz->refresh();
    }
}
