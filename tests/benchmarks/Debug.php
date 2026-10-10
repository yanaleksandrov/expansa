<?php

declare(strict_types=1);

use Expansa\Debug\Manager;

// run: php tests/benchmarks/Debug.php [--iterations=N]
// what Debug adds to every request: the bootstrap.php setup and a PHP warning on the handled path;
// the package is new, so there is no baseline: the cases are measured against doing nothing
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['iterations:']);
$iterations = (int) ($options['iterations'] ?? 20000);

/**
 * Best average time of one call in microseconds over the rounds.
 *
 * @param callable $callback
 * @param int      $iterations
 * @param int      $rounds
 * @return float
 */
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

// the bootstrap.php shape of configure()
$configure = function (Manager $debug): void {
    $debug->configure(
        view: EX_PATH . 'dashboard/views/fallback/debug.php',
        details: false,
        report: fn (Throwable $e, string $id, array $context) => null,
        warning: fn (ErrorException $e) => null,
        context: fn () => ['method' => $_SERVER['REQUEST_METHOD'] ?? '', 'user' => fn () => null],
        json: fn () => false,
        editor: '',
        collapse: [EX_PATH . 'expansa/', EX_PATH . 'vendor/'],
    );
};

$results = [];

$results['configure()'] = measure(function () use ($configure) {
    $configure(new Manager(console: false));
}, $iterations);

// once per process; the handlers are restored so they do not pile up, the shutdown functions do
$results['register()'] = measure(function () use ($configure) {
    $debug = new Manager(console: false);
    $configure($debug);
    $debug->register();
    restore_error_handler();
    restore_exception_handler();
}, (int) ($iterations / 10));

ini_set('display_errors', '0');
ini_set('log_errors', '0');

$results['warning, PHP'] = measure(fn () => trigger_error('benchmark', E_USER_DEPRECATED), $iterations);

$debug = new Manager(console: false);
$configure($debug);
$debug->register();
$results['warning, once'] = measure(fn () => trigger_error('benchmark', E_USER_DEPRECATED), $iterations);
restore_error_handler();
restore_exception_handler();

$opcache = function_exists('opcache_get_status') && opcache_get_status() ? 'on' : 'off';
printf("PHP %s, opcache %s, %d iterations, time of one call in µs\n\n", PHP_VERSION, $opcache, $iterations);
foreach ($results as $case => $time) {
    printf("%-16s %8.2f\n", $case, $time);
}
