<?php

declare(strict_types=1);

use Expansa\Log\Handlers\File;
use Expansa\Log\Logger;

// run: php tests/benchmarks/Log.php [--baseline=<git ref>] [--iterations=N]
// the baseline is the Log package of a commit, loaded under the LogBaseline namespace
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 20000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-log-bench-' . getmypid();

$files = shell_exec('git -C ' . escapeshellarg($root) . ' ls-tree -r --name-only ' . escapeshellarg($ref) . ' expansa-cms/expansa/Log');
if (! is_string($files) || trim($files) === '') {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach (explode("\n", trim($files)) as $file) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:$file"));
    $target = $tmp . '/baseline/' . substr($file, strlen('expansa-cms/expansa/Log/'));
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('Expansa\Log', 'LogBaseline', $source));
    touch($target, time() - 60);
}
spl_autoload_register(function (string $class) use ($tmp) {
    if (str_starts_with($class, 'LogBaseline\\')) {
        require $tmp . '/baseline/' . str_replace('\\', '/', substr($class, 12)) . '.php';
    }
});

function measure(callable $callback, int $iterations): float
{
    $callback();
    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }

    return (hrtime(true) - $start) / $iterations / 1000;
}

// the handler was FileHandler before the rename
$baselineFile = is_file("$tmp/baseline/Handlers/File.php") ? 'LogBaseline\Handlers\File' : 'LogBaseline\Handlers\FileHandler';

$old = new LogBaseline\Logger('app', [new $baselineFile("$tmp/old.log", 'debug')]);
$new = new Logger('app', [new File("$tmp/new.log", 'debug')]);

$oldQuiet = new LogBaseline\Logger('app', [new $baselineFile("$tmp/old-quiet.log", 'error')]);
$newQuiet = new Logger('app', [new File("$tmp/new-quiet.log", 'error')]);

$context = ['user' => 42, 'ip' => '127.0.0.1', 'tags' => ['auth', 'login']];

$cases = [
    'below level'  => [fn () => $oldQuiet->debug('Skipped'), fn () => $newQuiet->debug('Skipped')],
    'message'      => [fn () => $old->info('User logged in'), fn () => $new->info('User logged in')],
    'with context' => [fn () => $old->info('User logged in', $context), fn () => $new->info('User logged in', $context)],
];

printf("%-14s %12s %12s %8s\n", 'case', "$ref, µs", 'current, µs', 'speedup');
foreach ($cases as $name => [$baseline, $current]) {
    $a = measure($baseline, $iterations);
    $b = measure($current, $iterations);
    printf("%-14s %12.2f %12.2f %7.1fx\n", $name, $a, $b, $a / $b);
}

$new->popHandler();
array_map('unlink', glob("$tmp/*.log"));
