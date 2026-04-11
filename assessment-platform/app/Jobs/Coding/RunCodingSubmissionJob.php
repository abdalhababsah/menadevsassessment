<?php

namespace App\Jobs\Coding;

use App\Contracts\CodeRunners\CodeRunner;
use App\Contracts\CodeRunners\TestCaseResult;
use App\Models\AttemptCodingSubmission;
use App\Models\AttemptCodingTestResult;
use App\Models\CodingTestCase;
use App\Services\Scoring\QuizScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RunCodingSubmissionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $submissionId,
    ) {}

    public function handle(CodeRunner $runner, QuizScoringService $scoring): void
    {
        /** @var AttemptCodingSubmission|null $submission */
        $submission = AttemptCodingSubmission::query()
            ->with(['answer.question', 'answer.attempt'])
            ->find($this->submissionId);

        if ($submission === null || $submission->answer === null || $submission->answer->question === null) {
            return;
        }

        /** @var Collection<int, CodingTestCase> $testCases */
        $testCases = CodingTestCase::query()
            ->where('question_id', $submission->answer->question->id)
            ->get();

        $runnerInput = $testCases
            ->map(fn (CodingTestCase $testCase) => [
                'name' => "Case #{$testCase->id}",
                'input' => (string) $testCase->input,
                'expected_output' => (string) $testCase->expected_output,
            ])
            ->all();

        $result = $runner->run(
            (string) $submission->code,
            (string) $submission->language,
            $runnerInput,
        );

        DB::transaction(function () use ($submission, $testCases, $result) {
            // Clear any prior test results before writing new ones.
            $submission->testResults()->delete();

            foreach ($result->results as $index => $caseResult) {
                /** @var TestCaseResult $caseResult */
                $testCase = $testCases[$index] ?? null;
                if ($testCase === null) {
                    continue;
                }

                AttemptCodingTestResult::create([
                    'coding_submission_id' => $submission->id,
                    'test_case_id' => $testCase->id,
                    'passed' => $caseResult->passed,
                    'actual_output' => $caseResult->output,
                    'runtime_ms' => $caseResult->executionTimeMs !== null
                        ? (int) round($caseResult->executionTimeMs)
                        : null,
                    'memory_kb' => null,
                    'error' => $caseResult->error,
                ]);
            }
        });

        // Re-score the parent attempt so auto_score/final_score reflect the
        // new test results.
        $scoring->recalculate($submission->answer->attempt);
    }
}
