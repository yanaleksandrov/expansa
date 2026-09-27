<?php

declare(strict_types=1);

use Expansa\Patterns\Facade;

// run: php tests/benchmarks/Facade.php [--baseline=<git ref or file>] [--iterations=N]
// the baseline is Patterns/Facade.php of a commit or a file, loaded as FacadeBaseline
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 200000);
$ref        = $options['baseline'] ?? 'HEAD';

$source = is_file($ref)
    ? file_get_contents($ref)
    : shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . ' show ' . escapeshellarg("$ref:expansa-cms/expansa/Patterns/Facade.php") . ' 2>&1');
if (! is_string($source) || ! str_starts_with($source, '<?php')) {
    fwrite(STDERR, "Cannot load the baseline $ref: $source" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
$file = tempnam(sys_get_temp_dir(), 'facade');
file_put_contents($file, preg_replace('/^((?:abstract )?class) Facade\b/m', '$1 FacadeBaseline', $source, 1));
touch($file, time() - 60);
require $file;
unlink($file);

final class Target
{
    public function value(int $n): int
    {
        return $n;
    }
}

final class Current extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return Target::class;
    }
}

final class Baseline extends Expansa\Patterns\FacadeBaseline
{
    protected static function getStaticClassAccessor(): string
    {
        return Target::class;
    }
}

function measure(callable $callback, int $iterations, int $rounds = 5): float
{
    $callback();
    $best = INF;
    for ($r = 0; $r < $rounds; $r++) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }
        $best = min($best, (hrtime(true) - $start) / $iterations);
    }

    return $best;
}

$a = measure(fn () => Baseline::value(1), $iterations);
$b = measure(fn () => Current::value(1), $iterations);

printf("%-14s %12s %12s %8s\n", 'case', (is_file($ref) ? basename($ref) : $ref) . ', ns', 'current, ns', 'diff');
printf("%-14s %12.1f %12.1f %+7.1f%%\n", 'facade call', $a, $b, ($b - $a) / $a * 100);
