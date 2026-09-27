<?php

declare(strict_types=1);

namespace Expansa\Ai\Validators;

use Expansa\Ai\Contracts\Validator;

/**
 * Checks that generated paths are safe to write on any file system and use allowed file types.
 */
final class Paths implements Validator
{
    /**
     * Stores the allowed file types and whether tests are required.
     */
    public function __construct(

        /**
         * Allowed file extensions in lower case.
         *
         * @var string[]
         */
        public readonly array $extensions = ['php', 'json', 'md', 'css', 'js', 'html', 'svg', 'txt'],

        /**
         * Whether at least one PHP file under `tests/` is required.
         */
        public readonly bool $requireTests = true,
    ) {}

    /**
     * Rejects unsafe or disallowed paths, paths that clash on case-insensitive systems, and missing tests.
     *
     * @param array<string, string> $files Relative paths mapped to source
     * @return string[] Validation errors, or an empty array
     */
    public function validate(array $files): array
    {
        $errors = [];
        $seen = [];
        $tests = false;
        foreach (array_keys($files) as $path) {
            // numeric paths become integer keys
            $path = (string) $path;
            if (! $this->isSafe($path)) {
                $errors[] = "Unsafe generated file path: {$path}";
                continue;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (! in_array($extension, $this->extensions, true)) {
                $errors[] = "Unsupported generated file type: {$path}";
            }

            $key = strtolower($path);
            if (isset($seen[$key])) {
                $errors[] = "Generated file paths differ only by case: {$seen[$key]} and {$path}";
            }

            $seen[$key] = $path;
            $tests = $tests || $extension === 'php' && str_starts_with($path, 'tests/');
        }

        if ($this->requireTests && ! $tests && $files !== []) {
            $errors[] = 'The extension has no PHPUnit tests under tests/.';
        }

        return $errors;
    }

    /**
     * Allows relative paths of letters, digits, ".", "_", "-" without traversal or Windows device names.
     *
     * @param string $path Generated file path
     */
    private function isSafe(string $path): bool
    {
        if (! preg_match('~^[A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)*$~D', $path)) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            // Windows drops trailing dots, so "a." equals "a" and "." or ".." traverse
            if (str_ends_with($segment, '.') || preg_match('~^(?:con|prn|aux|nul|com\d|lpt\d)(?:\.|$)~i', $segment)) {
                return false;
            }
        }

        return true;
    }
}
