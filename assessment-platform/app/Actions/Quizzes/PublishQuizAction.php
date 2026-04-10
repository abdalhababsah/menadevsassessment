<?php

namespace App\Actions\Quizzes;

use App\Enums\QuizStatus;
use App\Exceptions\QuizPublishException;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Services\AuditLogger;

final class PublishQuizAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(Quiz $quiz): Quiz
    {
        $this->validate($quiz);

        $quiz->update(['status' => QuizStatus::Published]);

        $this->audit->log('quiz.published', $quiz);

        return $quiz->refresh();
    }

    private function validate(Quiz $quiz): void
    {
        $quiz->load(['sections.sectionQuestions']);

        if ($quiz->sections->isEmpty()) {
            throw QuizPublishException::noSections();
        }

        foreach ($quiz->sections as $section) {
            /** @var QuizSection $section */
            if ($section->sectionQuestions->isEmpty()) {
                throw QuizPublishException::emptySection($section->title);
            }

            foreach ($section->sectionQuestions as $sectionQuestion) {
                $exists = Question::query()
                    ->whereKey($sectionQuestion->question_id)
                    ->exists();

                if (! $exists) {
                    throw QuizPublishException::missingQuestion($sectionQuestion->question_id);
                }
            }
        }
    }
}
