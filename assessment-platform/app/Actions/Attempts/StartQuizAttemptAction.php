<?php

namespace App\Actions\Attempts;

use App\Enums\AnswerStatus;
use App\Enums\AttemptStatus;
use App\Enums\RlhfReviewStatus;
use App\Models\Candidate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use App\Models\QuizSection;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class StartQuizAttemptAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(
        Quiz $quiz,
        Candidate $candidate,
        ?QuizInvitation $invitation = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): QuizAttempt {
        return DB::transaction(function () use ($quiz, $candidate, $invitation, $ipAddress, $userAgent): QuizAttempt {
            $existing = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('candidate_id', $candidate->id)
                ->where('status', AttemptStatus::InProgress)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $quiz->load(['sections.sectionQuestions.question']);

            $firstSection = $quiz->sections->first();
            if ($firstSection === null) {
                throw new RuntimeException('Quiz has no sections.');
            }

            $firstQuestion = $this->firstQuestionOf($firstSection);
            if ($firstQuestion === null) {
                throw new RuntimeException('Quiz has no questions.');
            }

            $now = now();

            /** @var QuizAttempt $attempt */
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'candidate_id' => $candidate->id,
                'invitation_id' => $invitation?->id,
                'current_section_id' => $firstSection->id,
                'current_question_id' => $firstQuestion['question_id'],
                'started_at' => $now,
                'section_started_at' => $now,
                'question_started_at' => $now,
                'status' => AttemptStatus::InProgress,
                'rlhf_review_status' => RlhfReviewStatus::NotRequired,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            $this->snapshotAnswers($attempt, $quiz);

            if ($invitation !== null) {
                $invitation->increment('uses_count');
            }

            $this->audit->log('quiz.attempt_started', $attempt, [
                'quiz_id' => $quiz->id,
                'candidate_id' => $candidate->id,
                'invitation_id' => $invitation?->id,
            ]);

            return $attempt->refresh();
        });
    }

    private function snapshotAnswers(QuizAttempt $attempt, Quiz $quiz): void
    {
        foreach ($quiz->sections as $section) {
            foreach ($section->sectionQuestions as $sectionQuestion) {
                $attempt->answers()->create([
                    'question_id' => $sectionQuestion->question_id,
                    'question_version' => $sectionQuestion->question_version,
                    'status' => AnswerStatus::Unanswered,
                ]);
            }
        }
    }

    /**
     * @return array{question_id: int, section_question_id: int}|null
     */
    private function firstQuestionOf(QuizSection $section): ?array
    {
        $sectionQuestion = $section->sectionQuestions->first();
        if ($sectionQuestion === null) {
            return null;
        }

        return [
            'question_id' => (int) $sectionQuestion->question_id,
            'section_question_id' => (int) $sectionQuestion->id,
        ];
    }
}
