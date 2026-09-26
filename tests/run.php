<?php

declare(strict_types=1);

// run: php tests/run.php
// every test declares its own EX_PATH and check(), so each one runs in a separate process
$failed = [];

foreach (glob(__DIR__ . '/*.php') as $test) {
    // glob() and __FILE__ use different slashes on Windows, so the paths themselves never match
    if (basename($test) === basename(__FILE__)) {
        continue;
    }

    $name = basename($test, '.php');
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($test) . ' 2>&1', $output, $code);

    $fails = array_filter($output, fn (string $line) => ! str_starts_with($line, 'ok   '));
    printf("%-14s %s\n", $name, $code === 0 ? count($output) . ' ok' : 'FAILED');

    if ($code !== 0) {
        $failed[] = $name;
        echo '    ' . implode(PHP_EOL . '    ', $fails) . PHP_EOL;
    }

    $output = [];
}

// static analysis, when the Composer dev dependencies are installed
$phpstan = __DIR__ . '/../vendor/bin/phpstan';
if (is_file($phpstan)) {
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpstan) . ' analyse --no-progress --memory-limit=1G --error-format=raw -c ' . escapeshellarg(__DIR__ . '/../phpstan.neon') . ' 2>&1', $output, $code);

    $errors = array_filter($output, fn (string $line) => str_contains($line, 'identifier='));
    printf("%-14s %s\n", 'PHPStan', $code === 0 ? 'no new errors' : 'FAILED');

    if ($code !== 0) {
        $failed[] = 'PHPStan';
        echo '    ' . implode(PHP_EOL . '    ', $errors ?: $output) . PHP_EOL;
    }
}

echo PHP_EOL . ($failed ? 'Failed: ' . implode(', ', $failed) : 'All tests passed') . PHP_EOL;

exit($failed ? 1 : 0);
