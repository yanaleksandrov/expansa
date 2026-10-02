<?php

declare(strict_types=1);

namespace Expansa\Ai\Validators;

use Expansa\Ai\Contracts\Validator;
use Expansa\Ai\Internal\Tokens;

/**
 * Rejects PHP constructs that run arbitrary code or shell commands.
 * Works on tokens, so it catches direct calls only: it is a guard against model mistakes, not a sandbox.
 */
final class Policy implements Validator
{
    /**
     * Include and require tokens.
     */
    private const array INCLUDES = [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE];

    /**
     * Stores the forbidden function names.
     */
    public function __construct(

        /**
         * Functions generated code must not call, in lower case.
         *
         * @var string[]
         */
        public readonly array $functions = [
            'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'pcntl_exec', 'create_function', 'dl',
        ],
    ) {}

    /**
     * Reports `eval`, backtick shell execution, forbidden function calls, and includes of variable paths.
     *
     * @param array<string, string> $files Relative paths mapped to source
     * @return string[] Validation errors, or an empty array
     */
    public function validate(array $files): array
    {
        $forbidden = array_flip($this->functions);
        $errors = [];
        foreach ($files as $path => $source) {
            if (strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }

            // a backtick expression is one opening and one closing token
            $shell = false;
            $tokens = Tokens::significant($source);
            foreach ($tokens as $i => [$id, $text, $line]) {
                $shell = $id === '`' ? ! $shell : $shell;
                $problem = match (true) {
                    $id === '`' => $shell ? 'shell execution with backticks' : null,
                    $id === T_EVAL => 'eval()',
                    in_array($id, self::INCLUDES, true) && ($tokens[$i + 1][0] ?? null) === T_VARIABLE
                        => "{$text} of a variable path",
                    Tokens::isFunctionCall($tokens, $i) && isset($forbidden[Tokens::name($text)])
                        => ltrim($text, '\\') . '()',
                    default => null,
                };

                if ($problem !== null) {
                    $errors[] = "{$path}:{$line}: {$problem} is not allowed.";
                }
            }
        }

        return $errors;
    }
}
