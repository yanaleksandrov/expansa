<?php

declare(strict_types=1);

// shared by tests/*.php: EX_PATH, the autoloader, check() and throws()
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

/**
 * Print the check result, a failed check makes the test exit with 1.
 *
 * @param string $title
 * @param bool   $condition
 * @return void
 */
function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

/**
 * Whether the callback throws an exception of the class.
 *
 * @param callable     $callback
 * @param class-string $class
 * @return bool
 */
function throws(callable $callback, string $class = Throwable::class): bool
{
    try {
        $callback();
    } catch (Throwable $e) {
        return $e instanceof $class;
    }

    return false;
}
