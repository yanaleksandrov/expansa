<?php

declare(strict_types=1);

namespace Expansa\Ai\Validators;

use Expansa\Ai\Contracts\Validator;
use ParseError;

/**
 * Checks PHP syntax of generated files without executing source code.
 */
final class Php implements Validator
{
    /**
     * Parses PHP files and reports syntax failures for a generation repair pass.
     *
     * @param array<string, string> $files Relative paths mapped to source
     * @return string[] Validation errors, or an empty array
     */
    public function validate(array $files): array
    {
        $errors = [];
        foreach ($files as $path => $source) {
            if (strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }

            try {
                token_get_all($source, TOKEN_PARSE);
            } catch (ParseError $error) {
                $errors[] = "{$path}:{$error->getLine()}: {$error->getMessage()}";
            }
        }

        return $errors;
    }
}
