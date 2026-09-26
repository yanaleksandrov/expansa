<?php

declare(strict_types=1);

use Expansa\Support\ClassMap;

// run: php tests/benchmarks/Autoload.php [--baseline=<git ref>] [--iterations=N] [--runs=N]
// compares autoload.php of a commit with the current one, with and without the class map of `artisan autoload:dump`:
// 1. autoloader calls for missing classes (class_exists() of a name that does not exist asks the autoloader every time)
// 2. loading every class of the project in a fresh process with a warm opcache: opcache.file_cache keeps
//    compiled files between processes, as the shared memory of a web server does
const EX_PATH = __DIR__ . '/../../expansa-cms/';

$options    = getopt('', ['baseline:', 'iterations:', 'runs:', 'child:', 'work:']);
$iterations = (int) ($options['iterations'] ?? 200);
$runs       = (int) ($options['runs'] ?? 15);
$ref        = $options['baseline'] ?? 'HEAD';
$work       = $options['work'] ?? sys_get_temp_dir() . '/expansa-autoload-bench';
$classmap   = EX_PATH . 'cache/classmap.php';

// child: register one autoloader, load every class once and print the time in µs and the number of loaded classes
if (isset($options['child'])) {
    $classes = require $work . '/classes.php';
    $start   = hrtime(true);
    require $work . '/' . $options['child'] . '.php';

    $loaded = 0;
    foreach ($classes as $class) {
        // a class may need a library the project does not ship, e.g. Psr\Log for Spatie\ImageOptimizer
        try {
            $loaded += (int) (class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class));
        } catch (Error) {
        }
    }

    $missing = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        foreach ($classes as $class) {
            class_exists($class . 'Missing');
        }
    }

    printf("%.1f %d %.3f\n", ($missing - $start) / 1000, $loaded, (hrtime(true) - $missing) / 1000 / $iterations / count($classes));
    exit;
}

require_once EX_PATH . 'autoload.php';

$baseline = shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . ' show ' . escapeshellarg("$ref:expansa-cms/autoload.php") . ' 2>&1');
if (! is_string($baseline) || ! str_starts_with($baseline, '<?php')) {
    fwrite(STDERR, "Cannot load the baseline $ref: $baseline" . PHP_EOL);
    exit(1);
}

// compiled files of a previous run would hide changes, as timestamps are not checked below
$clear = function (string $dir) use (&$clear): void {
    foreach (glob($dir . '/{,.}*[!.]*', GLOB_BRACE) ?: [] as $path) {
        is_dir($path) ? $clear($path) : unlink($path);
    }
    @rmdir($dir);
};
$clear($work);
mkdir($work . '/opcache', 0777, true);

$classes = ClassMap::scan(EX_PATH);
file_put_contents($work . '/classes.php', '<?php return ' . var_export(array_keys($classes), true) . ';');
file_put_contents($work . '/baseline.php', $baseline);
copy(EX_PATH . 'autoload.php', $work . '/current.php');
// fresh files skip opcache (opcache.file_update_protection)
foreach (glob($work . '/*.php') as $file) {
    touch($file, time() - 60);
}

// the class map of the project is restored after the run
$saved = is_file($classmap) ? file_get_contents($classmap) : null;

$php = escapeshellarg(PHP_BINARY) . ' -d opcache.enable_cli=1 -d opcache.file_cache=' . escapeshellarg($work . '/opcache')
    . ' -d opcache.validate_timestamps=0 ' . escapeshellarg(__FILE__) . ' --work=' . escapeshellarg($work) . " --iterations=$iterations";

$variants = [
    $ref             => ['baseline', false],
    'current'        => ['current', false],
    'current + map'  => ['current', true],
];

$results = [];
try {
    foreach ($variants as $name => [$file, $withMap]) {
        if ($withMap) {
            ClassMap::dump($classmap, $classes);
            touch($classmap, time() - 60);
        } elseif (is_file($classmap)) {
            unlink($classmap);
        }

        exec("$php --child=$file");
        $load = $miss = [];
        for ($i = 0; $i < $runs; $i++) {
            [$time, $loaded, $missing] = explode(' ', trim((string) shell_exec("$php --child=$file")));
            $load[] = (float) $time;
            $miss[] = (float) $missing;
        }
        sort($load);
        sort($miss);
        $results[$name] = [$load[intdiv($runs, 2)] / 1000, $miss[intdiv($runs, 2)], (int) $loaded];
    }
} finally {
    $saved === null ? @unlink($classmap) : file_put_contents($classmap, $saved);
}

$base = $results[$ref];
printf("%d classes of the project, medians of %d runs\n\n", count($classes), $runs);
printf("%-14s %14s %8s %20s %8s %10s\n", 'autoloader', 'load all, ms', '', 'missing class, µs', '', 'loaded');
foreach ($results as $name => [$load, $miss, $loaded]) {
    printf("%-14s %14.2f %7.1fx %20.3f %7.1fx %6d/%d\n", $name, $load, $base[0] / $load, $miss, $base[1] / $miss, $loaded, count($classes));
}
