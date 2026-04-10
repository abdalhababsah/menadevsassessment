<?php

namespace App\Actions\Quizzes;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class DuplicateQuizAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(Quiz $original, User $duplicator): Quiz
    {
        return DB::transaction(function () use ($original, $duplicator): Quiz {
            $original->load(['sections.sectionQuestions']);

            $copy = Quiz::create([
                'title' => $original->title.' (Copy)',
                'description' => $original->description,
                'time_limit_seconds' => $original->time_limit_seconds,
                'passing_score' => $original->passing_score,
                'randomize_questions' => $original->randomize_questions,
                'randomize_options' => $original->randomize_options,
                'navigation_mode' => $original->navigation_mode,
                'camera_enabled' => $original->camera_enabled,
                'anti_cheat_enabled' => $original->anti_cheat_enabled,
                'max_fullscreen_exits' => $original->max_fullscreen_exits,
                'starts_at' => null,
                'ends_at' => null,
                'status' => QuizStatus::Draft,
                'created_by' => $duplicator->id,
            ]);

            foreach ($original->sections as $section) {
                /** @var QuizSection $section */
                $copiedSection = $copy->sections()->create([
                    'title' => $section->title,
                    'description' => $section->description,
                    'time_limit_seconds' => $section->time_limit_seconds,
                    'position' => $section->position,
                ]);

                foreach ($section->sectionQuestions as $sectionQuestion) {
                    $copiedSection->sectionQuestions()->create([
                        'question_id' => $sectionQuestion->question_id,
                        'question_version' => $sectionQuestion->question_version,
                        'points_override' => $sectionQuestion->points_override,
                        'time_limit_override_seconds' => $sectionQuestion->time_limit_override_seconds,
                        'position' => $sectionQuestion->position,
                    ]);
                }
            }

            $this->audit->log('quiz.duplicated', $copy, [
                'source_quiz_id' => $original->id,
                'source_title' => $original->title,
            ]);

            return $copy;
        });
    }
}
