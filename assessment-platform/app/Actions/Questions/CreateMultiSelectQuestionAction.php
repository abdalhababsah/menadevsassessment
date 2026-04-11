<?php

namespace App\Actions\Questions;

use App\Actions\Quizzes\AttachQuestionToSectionAction;
use App\Data\Questions\MultiSelectQuestionData;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuizSection;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateMultiSelectQuestionAction
{
    public function __construct(
        private AuditLogger $audit,
        private AttachQuestionToSectionAction $attachAction,
    ) {}

    /**
     * Create a multi-select question. When `$quizSectionId` is provided the
     * question is also attached to that section inside the same transaction.
     */
    public function handle(
        MultiSelectQuestionData $data,
        User $creator,
        ?int $quizSectionId = null,
    ): Question {
        return DB::transaction(function () use ($data, $creator, $quizSectionId): Question {
            $question = Question::create([
                'type' => QuestionType::MultiSelect,
                'stem' => $data->stem,
                'instructions' => $data->instructions,
                'difficulty' => QuestionDifficulty::from($data->difficulty),
                'points' => $data->points,
                'time_limit_seconds' => $data->time_limit_seconds,
                'created_by' => $creator->id,
            ]);

            $question->tags()->sync($data->tags);

            foreach ($data->options as $option) {
                $question->options()->create([
                    'content' => $option->content,
                    'content_type' => $option->content_type,
                    'is_correct' => $option->is_correct,
                    'position' => $option->position,
                ]);
            }

            $this->audit->log('question.created', $question, [
                'type' => 'multi_select',
                'stem' => Str::limit($data->stem, 100),
            ]);

            if ($quizSectionId !== null) {
                $section = QuizSection::query()->findOrFail($quizSectionId);
                $this->attachAction->handle($section, $question);
            }

            return $question;
        });
    }
}
