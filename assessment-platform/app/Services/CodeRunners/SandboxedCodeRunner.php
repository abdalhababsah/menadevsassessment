<?php

namespace App\Services\CodeRunners;

use App\Contracts\CodeRunners\CodeRunner;
use App\Contracts\CodeRunners\TestRunResult;
use App\Exceptions\NotImplementedException;

final class SandboxedCodeRunner implements CodeRunner
{
    public function run(string $code, string $language, array $testCases): TestRunResult
    {
        // TODO: Implement sandboxed code execution
        throw new NotImplementedException('Sandboxed code runner is not yet implemented.');
    }
}
