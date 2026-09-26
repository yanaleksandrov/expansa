<?php

declare(strict_types=1);

use Expansa\Scheduler\Cron\CronExpression;
use Expansa\Scheduler\Scheduler;

// run: php tests/benchmarks/Scheduler.php [--iterations=N]
// the baseline is the original dragonmantank/cron-expression from vendor/, the fork started as its copy
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$vendor = __DIR__ . '/../../vendor/autoload.php';
if (! is_file($vendor) || (require $vendor) === false || ! class_exists(Cron\CronExpression::class)) {
    fwrite(STDERR, 'Install the Composer dev dependencies: dragonmantank/cron-expression is the baseline' . PHP_EOL);
    exit(1);
}

$options     = getopt('', ['iterations:']);
$iterations  = (int) ($options['iterations'] ?? 2000);
$expressions = ['* * * * *', '0 * * * *', '*/5 * * * *', '30 2 * * *', '0 9-17 * * 1-5', '0 0 1 * *', '0 0 L * *', '0 0 * * 5#2', '0 0 13 * 5', '15,45 */2 * JAN,JUL MON-FRI'];
$date        = new DateTime('2025-03-14 10:30', new DateTimeZone('Europe/London'));

function measure(callable $callback, int $iterations): float
{
    $callback();
    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }

    return (hrtime(true) - $start) / $iterations / 1000;
}

$cases = [
    'construct'   => fn (string $class) => function () use ($class, $expressions) {
        foreach ($expressions as $expression) {
            new $class($expression);
        }
    },
    'isDue'       => function (string $class) use ($expressions, $date) {
        $parsed = array_map(fn ($expression) => new $class($expression), $expressions);

        return function () use ($parsed, $date) {
            foreach ($parsed as $expression) {
                $expression->isDue($date);
            }
        };
    },
    'nextRunDate' => function (string $class) use ($expressions, $date) {
        $parsed = array_map(fn ($expression) => new $class($expression), $expressions);

        return function () use ($parsed, $date) {
            foreach ($parsed as $expression) {
                $expression->getNextRunDate($date);
            }
        };
    },
    'prevRunDate' => function (string $class) use ($expressions, $date) {
        $parsed = array_map(fn ($expression) => new $class($expression), $expressions);

        return function () use ($parsed, $date) {
            foreach ($parsed as $expression) {
                $expression->getPreviousRunDate($date);
            }
        };
    },
];

printf("%-14s %14s %14s %8s\n", 'case, 10 expr', 'vendor, µs', 'fork, µs', 'speedup');
foreach ($cases as $name => $factory) {
    $baseline = measure($factory(Cron\CronExpression::class), $iterations);
    $fork     = measure($factory(CronExpression::class), $iterations);
    printf("%-14s %14.2f %14.2f %7.1fx\n", $name, $baseline, $fork, $baseline / $fork);
}

// a whole scheduler tick: 100 jobs, none of them due, which is the usual case
$scheduler = new Scheduler();
for ($i = 0; $i < 100; $i++) {
    $scheduler->call(fn () => null)->at($expressions[$i % count($expressions)] === '* * * * *' ? '59 23 31 12 *' : $expressions[$i % count($expressions)])->when(false);
}
printf("%-14s %14s %14.2f\n", 'run, 100 jobs', '', measure(fn () => $scheduler->resetRun()->run($date), $iterations));
