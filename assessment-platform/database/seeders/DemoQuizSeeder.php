<?php

namespace Database\Seeders;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Enums\QuizNavigationMode;
use App\Enums\QuizStatus;
use App\Models\CodingQuestionConfig;
use App\Models\CodingTestCase;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds a small demo quiz so a freshly-bootstrapped environment has
 * something visible in the admin UI for QA and local development.
 *
 * Creates one quiz, one section, and one of each non-RLHF question type
 * (single-select, multi-select, coding). RLHF is intentionally omitted
 * because it requires a criterion/form-field scaffolding that belongs in
 * a separate RLHF demo seeder.
 */
class DemoQuizSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('is_super_admin', true)->first() ?? User::factory()->create();

        $quiz = Quiz::query()->firstOrCreate(
            ['title' => 'Demo Quiz'],
            [
                'description' => 'A small demo quiz seeded for local QA. Covers MCQ, multi-select, and coding.',
                'time_limit_seconds' => 1800,
                'passing_score' => 70.00,
                'randomize_questions' => false,
                'randomize_options' => false,
                'navigation_mode' => QuizNavigationMode::Free,
                'camera_enabled' => false,
                'anti_cheat_enabled' => false,
                'max_fullscreen_exits' => 3,
                'status' => QuizStatus::Published,
                'created_by' => $author->id,
            ],
        );

        $section = QuizSection::query()->firstOrCreate(
            ['quiz_id' => $quiz->id, 'title' => 'Fundamentals'],
            [
                'position' => 0,
                'time_limit_seconds' => null,
            ],
        );

        // ---------- Single-select ----------
        $single = Question::query()->create([
            'type' => QuestionType::SingleSelect,
            'stem' => 'Which HTTP status code signals a successful resource creation?',
            'instructions' => null,
            'difficulty' => QuestionDifficulty::Easy,
            'points' => 2.00,
            'time_limit_seconds' => null,
            'version' => 1,
            'parent_question_id' => null,
            'created_by' => $author->id,
        ]);

        QuestionOption::query()->create([
            'question_id' => $single->id,
            'content_type' => 'text',
            'content' => '200 OK',
            'is_correct' => false,
            'position' => 0,
        ]);
        QuestionOption::query()->create([
            'question_id' => $single->id,
            'content_type' => 'text',
            'content' => '201 Created',
            'is_correct' => true,
            'position' => 1,
        ]);
        QuestionOption::query()->create([
            'question_id' => $single->id,
            'content_type' => 'text',
            'content' => '204 No Content',
            'is_correct' => false,
            'position' => 2,
        ]);

        // ---------- Multi-select ----------
        $multi = Question::query()->create([
            'type' => QuestionType::MultiSelect,
            'stem' => 'Which of the following are Laravel facades?',
            'instructions' => 'Pick all that apply.',
            'difficulty' => QuestionDifficulty::Medium,
            'points' => 3.00,
            'time_limit_seconds' => null,
            'version' => 1,
            'parent_question_id' => null,
            'created_by' => $author->id,
        ]);

        QuestionOption::query()->create([
            'question_id' => $multi->id,
            'content_type' => 'text',
            'content' => 'Cache',
            'is_correct' => true,
            'position' => 0,
        ]);
        QuestionOption::query()->create([
            'question_id' => $multi->id,
            'content_type' => 'text',
            'content' => 'Redis',
            'is_correct' => true,
            'position' => 1,
        ]);
        QuestionOption::query()->create([
            'question_id' => $multi->id,
            'content_type' => 'text',
            'content' => 'Doctrine',
            'is_correct' => false,
            'position' => 2,
        ]);

        // ---------- Coding ----------
        $coding = Question::query()->create([
            'type' => QuestionType::Coding,
            'stem' => 'Write a function that returns the string "hello".',
            'instructions' => 'Your program should print MATCH_ME_0 somewhere in the output.',
            'difficulty' => QuestionDifficulty::Easy,
            'points' => 5.00,
            'time_limit_seconds' => null,
            'version' => 1,
            'parent_question_id' => null,
            'created_by' => $author->id,
        ]);

        CodingQuestionConfig::query()->create([
            'question_id' => $coding->id,
            'allowed_languages' => ['python', 'javascript'],
            'starter_code' => ['python' => "def solution():\n    pass\n"],
            'time_limit_ms' => 2000,
            'memory_limit_mb' => 128,
        ]);

        CodingTestCase::query()->create([
            'question_id' => $coding->id,
            'input' => '',
            'expected_output' => 'MATCH_ME_0',
            'is_hidden' => false,
            'weight' => 1.00,
        ]);

        // Attach all three to the section.
        foreach ([$single, $multi, $coding] as $position => $question) {
            QuizSectionQuestion::query()->firstOrCreate(
                [
                    'quiz_section_id' => $section->id,
                    'question_id' => $question->id,
                ],
                [
                    'question_version' => 1,
                    'position' => $position,
                ],
            );
        }

        $this->command->info("Demo quiz '{$quiz->title}' seeded with 3 questions.");
    }
}
