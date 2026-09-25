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

echo PHP_EOL . ($failed ? 'Failed: ' . implode(', ', $failed) : 'All tests passed') . PHP_EOL;

exit($failed ? 1 : 0);
