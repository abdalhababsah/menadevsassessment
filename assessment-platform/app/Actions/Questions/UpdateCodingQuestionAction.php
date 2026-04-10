<?php

namespace App\Actions\Questions;

use App\Data\Questions\CodingQuestionData;
use App\Data\Questions\CodingTestCaseData;
use App\Enums\QuestionDifficulty;
use App\Models\Question;
use App\Services\AuditLogger;
use App\Services\QuestionBank\QuestionVersioningService;
use Illuminate\Support\Facades\DB;

final class UpdateCodingQuestionAction
{
    public function __construct(
        private AuditLogger $audit,
        private QuestionVersioningService $versioning,
    ) {}

    public function handle(
        Question $question,
        CodingQuestionData $data,
        bool $forceNewVersion = false,
        bool $forceInPlace = false,
    ): Question {
        $shouldFork = $forceNewVersion
            || ($this->versioning->isUsedInQuizzes($question) && ! $forceInPlace);

        if ($shouldFork) {
            $newQuestion = $this->versioning->forkNewVersion($question, [
                'stem' => $data->stem,
                'instructions' => $data->instructions,
                'difficulty' => QuestionDifficulty::from($data->difficulty),
                'points' => $data->points,
                'time_limit_seconds' => $data->time_limit_seconds,
                'tags' => $data->tags,
                'coding_config' => [
                    'allowed_languages' => $data->allowed_languages,
                    'starter_code' => $data->starter_code,
                    'time_limit_ms' => $data->time_limit_ms,
                    'memory_limit_mb' => $data->memory_limit_mb,
                ],
                'test_cases' => array_map(
                    fn (CodingTestCaseData $tc): array => [
                        'input' => $tc->input,
                        'expected_output' => $tc->expected_output,
                        'is_hidden' => $tc->is_hidden,
                        'weight' => $tc->weight,
                    ],
                    $data->test_cases,
                ),
            ]);

            $this->audit->log('question.versioned', $newQuestion, [
                'parent_id' => $question->id,
                'version' => $newQuestion->version,
            ]);

            return $newQuestion;
        }

        return DB::transaction(function () use ($question, $data): Question {
            $question->update([
                'stem' => $data->stem,
                'instructions' => $data->instructions,
                'difficulty' => QuestionDifficulty::from($data->difficulty),
                'points' => $data->points,
                'time_limit_seconds' => $data->time_limit_seconds,
            ]);

            $question->tags()->sync($data->tags);

            $question->codingConfig()->updateOrCreate(
                ['question_id' => $question->id],
                [
                    'allowed_languages' => $data->allowed_languages,
                    'starter_code' => $data->starter_code,
                    'time_limit_ms' => $data->time_limit_ms,
                    'memory_limit_mb' => $data->memory_limit_mb,
                ],
            );

            $question->testCases()->delete();
            foreach ($data->test_cases as $testCase) {
                $question->testCases()->create([
                    'input' => $testCase->input,
                    'expected_output' => $testCase->expected_output,
                    'is_hidden' => $testCase->is_hidden,
                    'weight' => $testCase->weight,
                ]);
            }

            $this->audit->log('question.updated', $question, ['type' => 'coding']);

            return $question->refresh();
        });
    }
}
