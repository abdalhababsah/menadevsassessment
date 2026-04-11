<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Jobs\Coding\RunCodingSubmissionJob;
use App\Models\AttemptAnswer;
use App\Models\AttemptCodingTestResult;
use App\Models\CodingTestCase;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CodingReviewController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function show(AttemptAnswer $attemptAnswer): Response
    {
        $attemptAnswer->load([
            'question.codingConfig',
            'attempt.candidate',
            'attempt.quiz',
            'codingSubmission.testResults',
        ]);

        abort_unless(
            $attemptAnswer->question?->type === QuestionType::Coding,
            404,
            'Answer is not a coding answer.',
        );

        $testCases = CodingTestCase::query()
            ->where('question_id', $attemptAnswer->question->id)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $submission = $attemptAnswer->codingSubmission;
        /** @var array<int, array<string, mixed>> $testResults */
        $testResults = [];
        if ($submission !== null) {
            foreach ($submission->testResults as $result) {
                /** @var AttemptCodingTestResult $result */
                $testCase = $testCases->get($result->test_case_id);
                $testResults[] = [
                    'id' => $result->id,
                    'test_case_id' => $result->test_case_id,
                    'test_case' => [
                        'input' => $testCase?->input,
                        'expected_output' => $testCase?->expected_output,
                        'is_hidden' => $testCase !== null && (bool) $testCase->is_hidden,
                    ],
                    'passed' => (bool) $result->passed,
                    'actual_output' => $result->actual_output,
                    'runtime_ms' => $result->runtime_ms,
                    'memory_kb' => $result->memory_kb,
                    'error' => $result->error,
                ];
            }
        }

        return Inertia::render('Admin/Results/CodingReview', [
            'answer' => [
                'id' => $attemptAnswer->id,
                'status' => $attemptAnswer->status->value,
                'auto_score' => $attemptAnswer->auto_score !== null ? (float) $attemptAnswer->auto_score : null,
                'reviewer_score' => $attemptAnswer->reviewer_score !== null ? (float) $attemptAnswer->reviewer_score : null,
                'question_stem' => $attemptAnswer->question->stem,
                'question_points' => (float) $attemptAnswer->question->points,
            ],
            'attempt' => [
                'id' => $attemptAnswer->attempt->id,
            ],
            'quiz' => [
                'id' => $attemptAnswer->attempt->quiz->id,
                'title' => $attemptAnswer->attempt->quiz->title,
            ],
            'candidate' => [
                'name' => $attemptAnswer->attempt->candidate?->name,
                'email' => $attemptAnswer->attempt->candidate?->email,
            ],
            'submission' => $submission !== null ? [
                'id' => $submission->id,
                'language' => $submission->language,
                'code' => $submission->code,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
            ] : null,
            'test_results' => $testResults,
            'permissions' => [
                'can_rerun' => Auth::user()?->hasPermissionTo('coding.rerun') ?? false,
                'can_override' => Auth::user()?->hasPermissionTo('coding.override') ?? false,
            ],
        ]);
    }

    public function rerun(AttemptAnswer $attemptAnswer): JsonResponse
    {
        $attemptAnswer->loadMissing(['codingSubmission']);

        abort_if(
            $attemptAnswer->codingSubmission === null,
            404,
            'No submission to re-run.',
        );

        RunCodingSubmissionJob::dispatch($attemptAnswer->codingSubmission->id);

        $this->auditLogger->log('coding.rerun', $attemptAnswer);

        return response()->json([
            'dispatched' => true,
        ]);
    }

    public function override(Request $request, AttemptAnswer $attemptAnswer): JsonResponse
    {
        $data = $request->validate([
            'reviewer_score' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $previous = $attemptAnswer->reviewer_score !== null ? (float) $attemptAnswer->reviewer_score : null;

        $attemptAnswer->update([
            'reviewer_score' => $data['reviewer_score'],
        ]);

        $this->auditLogger->log('coding.override', $attemptAnswer, [
            'previous_reviewer_score' => $previous,
            'new_reviewer_score' => (float) $data['reviewer_score'],
            'reason' => $data['reason'],
        ]);

        return response()->json([
            'overridden' => true,
            'reviewer_score' => (float) $data['reviewer_score'],
        ]);
    }
}
