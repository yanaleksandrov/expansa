<?php

declare(strict_types=1);

use Expansa\Facades\Hook;
use Expansa\Lifecycle\Manager;

// run: php tests/benchmarks/Lifecycle.php [--baseline=<git ref>] [--iterations=N]
// the baseline is the Lifecycle package of a commit, loaded under the LifecycleBaseline namespace
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 20000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-lifecycle-bench-' . getmypid();

$files = shell_exec('git -C ' . escapeshellarg($root) . ' ls-tree -r --name-only ' . escapeshellarg($ref) . ' expansa-cms/expansa/Lifecycle');
if (! is_string($files) || trim($files) === '') {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach (explode("\n", trim($files)) as $file) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:$file"));
    $target = $tmp . '/' . substr($file, strlen('expansa-cms/expansa/Lifecycle/'));
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('Expansa\Lifecycle', 'LifecycleBaseline', $source));
    touch($target, time() - 60);
}
spl_autoload_register(function (string $class) use ($tmp) {
    if (str_starts_with($class, 'LifecycleBaseline\\')) {
        require $tmp . '/' . str_replace('\\', '/', substr($class, 18)) . '.php';
    }
});

function measure(callable $callback, int $iterations, int $rounds = 5): float
{
    $callback();
    $best = INF;
    for ($r = 0; $r < $rounds; $r++) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }
        $best = min($best, (hrtime(true) - $start) / $iterations / 1000);
    }

    return $best;
}

// the bootstrap.php shape: five phases, six contexts, the fifth one matches
$request = function (object $app): void {
    if (method_exists($app, 'configure')) {
        $app->configure(hook: fn (string $name) => Hook::call($name), terminate: fn () => Hook::defer('terminate'));
    }
    foreach (['boot', 'configure', 'register', 'extensions', 'booted'] as $phase) {
        $app->phase($phase, true, fn () => null);
    }
    $app->context('cli', false, fn () => null)
        ->context('api', fn (string $uri) => str_starts_with($uri, '/api/'), fn () => null)
        ->context('install', false, fn () => null)
        ->context('auth', fn (string $uri) => in_array(trim($uri, '/'), ['sign-in', 'sign-up'], true), fn () => null)
        ->context('dashboard', fn (string $uri) => str_starts_with(trim($uri, '/'), 'dashboard'), fn () => null)
        ->context('web', true, fn () => null);
    $app->run('/dashboard/posts');
    $app->is('dashboard');
};

$a = measure(fn () => $request(new LifecycleBaseline\Manager()), $iterations);
$b = measure(fn () => $request(new Manager()), $iterations);

printf("%-14s %12s %12s %8s\n", 'case', "$ref, µs", 'current, µs', 'diff');
printf("%-14s %12.2f %12.2f %+7.1f%%\n", 'request', $a, $b, ($b - $a) / $a * 100);

array_map('unlink', glob("$tmp/*/*.php") ?: []);
array_map('unlink', glob("$tmp/*.php") ?: []);
