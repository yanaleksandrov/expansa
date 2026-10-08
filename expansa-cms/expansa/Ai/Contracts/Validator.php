<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

interface Validator
{
    /**
     * Checks generated paths and source before returning an artifact.
     * Returns actionable errors for the generator's repair pass.
     *
     * @param array<string, string> $files Relative paths mapped to source
     * @return string[] Validation errors, or an empty array
     */
    public function validate(array $files): array;
}
