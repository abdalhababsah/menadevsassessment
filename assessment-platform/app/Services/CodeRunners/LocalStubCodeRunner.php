<?php

namespace App\Services\CodeRunners;

use App\Contracts\CodeRunners\CodeRunner;
use App\Contracts\CodeRunners\TestCaseResult;
use App\Contracts\CodeRunners\TestRunResult;

/**
 * Dev-only stub runner for coding submissions.
 *
 * Performs a trivial "does the candidate's code contain the expected output?"
 * check for each test case. It's deterministic, has no external dependencies,
 * and lets the rest of the submission pipeline (job, persistence, scoring) be
 * tested end-to-end without a real sandbox.
 *
 * TODO: replace with a hardened sandbox (Judge0, nsjail, Firecracker, etc.)
 * before running candidate-supplied code in production.
 */
final class LocalStubCodeRunner implements CodeRunner
{
    public function run(string $code, string $language, array $testCases): TestRunResult
    {
        $results = [];
        $passed = 0;
        $failed = 0;
        $startedAt = microtime(true);

        foreach ($testCases as $index => $testCase) {
            $name = $testCase['name'] ?? "Test #{$index}";
            $expected = $testCase['expected_output'];

            // Extremely naive pass rule: the candidate's code must literally
            // contain the expected output. Good enough for dev + tests.
            $didPass = $expected !== '' && str_contains($code, $expected);

            if ($didPass) {
                $passed++;
            } else {
                $failed++;
            }

            $results[] = new TestCaseResult(
                name: $name,
                passed: $didPass,
                output: $didPass ? $expected : '(stub runner) pattern not found',
                error: null,
                executionTimeMs: 1.0,
            );
        }

        return new TestRunResult(
            compiled: true,
            passed: $passed,
            failed: $failed,
            total: count($testCases),
            results: $results,
            compileError: null,
            totalExecutionTimeMs: (microtime(true) - $startedAt) * 1000,
        );
    }
}
