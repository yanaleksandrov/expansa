<?php

declare(strict_types=1);

namespace Expansa\Debug\Internal;

use Throwable;
use TypeError;

/**
 * Data of an uncaught error for a page, a JSON response or the console. With details: the message,
 * the frames with the code around each line, the previous exceptions, the arguments of a TypeError
 * and the request; without them only that an error happened and its id, so a public site shows
 * nothing of its code.
 *
 * Strings are English: the page is for developers and must work when translations are broken.
 *
 * @internal Used by Manager only.
 *
 * @package Expansa\Debug
 */
final class Page
{
    /**
     * Lines of code shown before and after the line of a frame.
     */
    private const int CONTEXT_LINES = 10;

    /**
     * Frames with code; a deeper trace is cut, e.g. after an endless recursion.
     */
    private const int MAX_FRAMES = 50;

    /**
     * Flags of the JSON output: readable, never failing on a bad UTF-8 string.
     */
    public const int ENCODE_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Keys whose values are hidden on the page and in the log.
     */
    private const string SECRET_KEYS = '/pass|secret|token|key|auth|cookie|csrf|nonce|session/i';

    public function __construct(

        /**
         * Editor URL of a frame with {file} and {line}: `vscode://file/{file}:{line}`; empty without links.
         */
        public string $editor = '',

        /**
         * Directories of the frames shown collapsed, e.g. the core and vendor; resolved only for an error.
         *
         * @var string[]
         */
        public array $collapse = [],
    ) {}

    /**
     * Template variables: title, message, id, file, line, link, trace, arguments, previous and request.
     * Without details only title, message and id, the rest is empty.
     *
     * @param Throwable            $e
     * @param string               $id      Id of the error in the log.
     * @param array<string, mixed> $request Context of the request, shown with details only.
     * @param bool                 $details
     * @return array<string, mixed>
     */
    public function getData(Throwable $e, string $id = '', array $request = [], bool $details = false): array
    {
        if (! $details) {
            return [
                'title'     => 'Server Error',
                'message'   => 'Something went wrong. Please try again later.',
                'id'        => $id,
                'file'      => '',
                'line'      => 0,
                'link'      => '',
                'trace'     => [],
                'arguments' => [],
                'previous'  => [],
                'request'   => [],
            ];
        }

        return [
            'title'     => $e::class,
            'message'   => $e->getMessage(),
            'id'        => $id,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'link'      => $this->link($e->getFile(), $e->getLine()),
            'trace'     => $this->trace($e),
            'arguments' => $e instanceof TypeError ? $this->arguments($e) : [],
            'previous'  => $this->previous($e),
            'request'   => array_map($this->value(...), self::sanitize($request)),
        ];
    }

    /**
     * Body of a JSON response, in the `{ "message": ... }` shape of the API.
     *
     * @param Throwable $e
     * @param string    $id
     * @param bool      $details Add the class, the place and the frames.
     * @return array<string, mixed>
     */
    public function getPayload(Throwable $e, string $id = '', bool $details = false): array
    {
        if (! $details) {
            return ['message' => 'Something went wrong. Please try again later.', 'id' => $id];
        }

        return [
            'message'   => $e->getMessage(),
            'id'        => $id,
            'exception' => $e::class,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => array_map(
                fn (array $frame) => "{$frame['file']}:{$frame['line']} {$frame['call']}",
                $this->trace($e, false),
            ),
        ];
    }

    /**
     * The error with its trace and previous exceptions as plain text, for the console.
     *
     * @param Throwable $e
     * @param string    $id
     * @return string
     */
    public function getText(Throwable $e, string $id = ''): string
    {
        $text = $id !== '' ? "Error ID: $id" . PHP_EOL : '';

        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $text .= ($current === $e ? '' : PHP_EOL . 'Caused by ') . $current::class . ': ' . $current->getMessage() . PHP_EOL;

            foreach ($this->trace($current, false) as $i => $frame) {
                $text .= sprintf('  #%d %s:%d %s', $i, $frame['file'], $frame['line'], $frame['call']) . PHP_EOL;
            }
        }

        return $text;
    }

    /**
     * Hide the values of secret keys, nested arrays included: passwords, tokens, cookies.
     *
     * @param array<array-key, mixed> $values
     * @return array<array-key, mixed>
     */
    public static function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match(self::SECRET_KEYS, $key)) {
                $values[$key] = '********';
            } elseif (is_array($value)) {
                $values[$key] = self::sanitize($value);
            }
        }

        return $values;
    }

    /**
     * Frames with a file, starting from the place of the throw.
     *
     * @param Throwable $e
     * @param bool      $code Add the code around each line, the link and whether the frame is collapsed.
     * @return list<array<string, mixed>>
     */
    private function trace(Throwable $e, bool $code = true): array
    {
        $frames = [['file' => $e->getFile(), 'line' => $e->getLine(), 'call' => '']];

        foreach ($e->getTrace() as $frame) {
            if (count($frames) === self::MAX_FRAMES) {
                break;
            }

            if (isset($frame['file'])) {
                $frames[] = [
                    'file' => $frame['file'],
                    'line' => $frame['line'] ?? 0,
                    'call' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'] . '()',
                ];
            }
        }

        if (! $code) {
            return $frames;
        }

        // realpath() is slow, so the directories are resolved here and not on every request in the constructor
        $collapse = array_map(
            fn (string $path) => rtrim(str_replace('\\', '/', realpath($path) ?: $path), '/') . '/',
            array_filter($this->collapse),
        );

        foreach ($frames as $i => $frame) {
            [$frames[$i]['code'], $frames[$i]['start']] = $this->excerpt($frame['file'], $frame['line']);

            $frames[$i]['link']      = $this->link($frame['file'], $frame['line']);
            $frames[$i]['collapsed'] = $i > 0 && $this->isCollapsed($frame['file'], $collapse);
        }

        return $frames;
    }

    /**
     * Whether the file is under one of the directories.
     *
     * @param string   $file
     * @param string[] $directories Resolved, with forward slashes and a trailing one.
     * @return bool
     */
    private function isCollapsed(string $file, array $directories): bool
    {
        $file = str_replace('\\', '/', $file);

        foreach ($directories as $path) {
            if (strncasecmp($file, $path, strlen($path)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Editor URL of the place, empty without an editor.
     *
     * @param string $file
     * @param int    $line
     * @return string
     */
    private function link(string $file, int $line): string
    {
        return $this->editor === '' ? '' : strtr($this->editor, ['{file}' => str_replace('\\', '/', $file), '{line}' => $line]);
    }

    /**
     * Arguments of the call that failed with a TypeError; empty with zend.exception_ignore_args=On.
     *
     * @param TypeError $e
     * @return list<array{position: int, type: string, value: string}>
     */
    private function arguments(TypeError $e): array
    {
        $arguments = [];
        foreach (array_values($e->getTrace()[0]['args'] ?? []) as $i => $value) {
            $arguments[] = [
                'position' => $i + 1,
                'type'     => get_debug_type($value),
                'value'    => $this->export($value),
            ];
        }

        return $arguments;
    }

    /**
     * Short readable value: scalars as code, long strings cut, arrays by size, objects by class.
     *
     * @param mixed $value
     * @return string
     */
    private function export(mixed $value): string
    {
        return match (true) {
            is_string($value) && mb_strlen($value) > 200 => var_export(mb_substr($value, 0, 200), true) . '…',
            is_scalar($value) || $value === null         => var_export($value, true),
            is_array($value)                             => 'array(' . count($value) . ')',
            default                                      => get_debug_type($value),
        };
    }

    /**
     * Request value as shown: strings as they are, arrays as JSON, the rest by export().
     *
     * @param mixed $value
     * @return string
     */
    private function value(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_array($value)  => (string) json_encode($value, self::ENCODE_FLAGS),
            default           => $this->export($value),
        };
    }

    /**
     * Previous exceptions, the closest first.
     *
     * @param Throwable $e
     * @return list<array{title: string, message: string, file: string, line: int, link: string}>
     */
    private function previous(Throwable $e): array
    {
        $previous = [];
        while ($e = $e->getPrevious()) {
            $previous[] = [
                'title'   => $e::class,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'link'    => $this->link($e->getFile(), $e->getLine()),
            ];
        }

        return $previous;
    }

    /**
     * Lines around the given one and the number of the first of them.
     *
     * @param string $file
     * @param int    $line
     * @return array{string, int}
     */
    private function excerpt(string $file, int $line): array
    {
        // the file may be gone or never existed: eval, deleted cache
        $lines = is_file($file) ? file($file) : false;
        if (! $lines) {
            return ['', 0];
        }

        $start = max(1, $line - self::CONTEXT_LINES);

        return [rtrim(implode('', array_slice($lines, $start - 1, $line - $start + self::CONTEXT_LINES + 1))), $start];
    }
}
