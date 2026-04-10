<?php

namespace App\Actions\Attempts;

use App\Enums\AnswerStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptCodingSubmission;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RecordCodingAnswerAction
{
    public function handle(AttemptAnswer $answer, string $language, string $code, ?int $timeSpentSeconds = null): AttemptAnswer
    {
        $answer->loadMissing(['question', 'codingSubmission.testResults']);

        if ($answer->question === null) {
            throw new InvalidArgumentException('Answer has no linked question.');
        }

        return DB::transaction(function () use ($answer, $language, $code, $timeSpentSeconds): AttemptAnswer {
            /** @var AttemptCodingSubmission $submission */
            $submission = $answer->codingSubmission ?? new AttemptCodingSubmission([
                'attempt_answer_id' => $answer->id,
            ]);

            // Clear prior test results when resubmitting.
            if ($submission->exists) {
                $submission->testResults()->delete();
            }

            $submission->fill([
                'language' => $language,
                'code' => $code,
                'submitted_at' => now(),
            ])->save();

            $answer->update([
                'status' => AnswerStatus::Answered,
                'answered_at' => now(),
                'time_spent_seconds' => $timeSpentSeconds ?? $answer->time_spent_seconds,
            ]);

            return $answer->fresh(['codingSubmission.testResults']);
        });
    }
}
