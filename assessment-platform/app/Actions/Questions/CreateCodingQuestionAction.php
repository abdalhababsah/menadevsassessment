<?php

namespace App\Actions\Questions;

use App\Data\Questions\CodingQuestionData;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateCodingQuestionAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(CodingQuestionData $data, User $creator): Question
    {
        return DB::transaction(function () use ($data, $creator): Question {
            $question = Question::create([
                'type' => QuestionType::Coding,
                'stem' => $data->stem,
                'instructions' => $data->instructions,
                'difficulty' => QuestionDifficulty::from($data->difficulty),
                'points' => $data->points,
                'time_limit_seconds' => $data->time_limit_seconds,
                'created_by' => $creator->id,
            ]);

            $question->tags()->sync($data->tags);

            $question->codingConfig()->create([
                'allowed_languages' => $data->allowed_languages,
                'starter_code' => $data->starter_code,
                'time_limit_ms' => $data->time_limit_ms,
                'memory_limit_mb' => $data->memory_limit_mb,
            ]);

            foreach ($data->test_cases as $testCase) {
                $question->testCases()->create([
                    'input' => $testCase->input,
                    'expected_output' => $testCase->expected_output,
                    'is_hidden' => $testCase->is_hidden,
                    'weight' => $testCase->weight,
                ]);
            }

            $this->audit->log('question.created', $question, [
                'type' => 'coding',
                'stem' => Str::limit($data->stem, 100),
            ]);

            return $question;
        });
    }
}
