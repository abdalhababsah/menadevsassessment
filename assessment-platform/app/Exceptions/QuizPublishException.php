<?php

namespace App\Exceptions;

use RuntimeException;

final class QuizPublishException extends RuntimeException
{
    public static function noSections(): self
    {
        return new self('Quiz must have at least one section before it can be published.');
    }

    public static function emptySection(string $sectionTitle): self
    {
        return new self("Section '{$sectionTitle}' must contain at least one question.");
    }

    public static function missingQuestion(int $questionId): self
    {
        return new self("Question #{$questionId} no longer exists.");
    }
}
