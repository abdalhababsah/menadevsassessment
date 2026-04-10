<?php

namespace App\Actions\Attempts;

use App\Enums\AnswerStatus;
use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RecordSelectionAnswerAction
{
    public function handle(AttemptAnswer $answer, array $optionIds, ?int $timeSpentSeconds = null): AttemptAnswer
    {
        $answer->loadMissing(['question']);

        $question = $answer->question;

        if ($question === null) {
            throw new InvalidArgumentException('Answer has no linked question.');
        }

        if (! in_array($question->type, [QuestionType::SingleSelect, QuestionType::MultiSelect], true)) {
            throw new InvalidArgumentException('Question is not a selection type.');
        }

        if ($question->type === QuestionType::SingleSelect) {
            $optionIds = array_slice($optionIds, 0, 1);
        }

        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));

        return DB::transaction(function () use ($answer, $optionIds, $timeSpentSeconds): AttemptAnswer {
            $answer->selections()->delete();

            foreach ($optionIds as $optionId) {
                $answer->selections()->create([
                    'question_option_id' => $optionId,
                ]);
            }

            $answer->update([
                'status' => AnswerStatus::Answered,
                'answered_at' => now(),
                'time_spent_seconds' => $timeSpentSeconds ?? $answer->time_spent_seconds,
            ]);

            return $answer->fresh(['selections.option', 'question.options']);
        });
    }
}
