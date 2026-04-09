<?php

namespace App\Contracts\CodeRunners;

interface CodeRunner
{
    /**
     * Run code against a set of test cases in a sandboxed environment.
     *
     * @param  array<int, array{input: string, expected_output: string, name?: string}>  $testCases
     */
    public function run(string $code, string $language, array $testCases): TestRunResult;
}
