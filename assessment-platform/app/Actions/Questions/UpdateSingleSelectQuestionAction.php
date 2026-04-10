<?php

namespace App\Actions\Questions;

use App\Data\Questions\QuestionOptionData;
use App\Data\Questions\SingleSelectQuestionData;
use App\Enums\QuestionDifficulty;
use App\Models\Question;
use App\Services\AuditLogger;
use App\Services\QuestionBank\QuestionVersioningService;
use Illuminate\Support\Facades\DB;

final class UpdateSingleSelectQuestionAction
{
    public function __construct(
        private AuditLogger $audit,
        private QuestionVersioningService $versioning,
    ) {}

    public function handle(
        Question $question,
        SingleSelectQuestionData $data,
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
                'options' => array_map(
                    fn (QuestionOptionData $opt): array => [
                        'content' => $opt->content,
                        'content_type' => $opt->content_type,
                        'is_correct' => $opt->is_correct,
                        'position' => $opt->position,
                    ],
                    $data->options,
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

            $question->options()->delete();
            foreach ($data->options as $option) {
                $question->options()->create([
                    'content' => $option->content,
                    'content_type' => $option->content_type,
                    'is_correct' => $option->is_correct,
                    'position' => $option->position,
                ]);
            }

            $this->audit->log('question.updated', $question, ['type' => 'single_select']);

            return $question->refresh();
        });
    }
}
