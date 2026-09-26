<?php

declare(strict_types=1);

use Expansa\Assets\Manager;
use Expansa\Support\Url;

// run: php tests/benchmarks/Assets.php [--baseline=<git ref>] [--iterations=N]
// the baseline is the Assets package of a commit, loaded under the AssetsBaseline namespace
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

function url(string $path = ''): string
{
    return '/' . $path;
}

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 2000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-assets-bench-' . getmypid();

$files = shell_exec('git -C ' . escapeshellarg($root) . ' ls-tree -r --name-only ' . escapeshellarg($ref) . ' expansa-cms/expansa/Assets');
if (! is_string($files) || trim($files) === '') {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach (explode("\n", trim($files)) as $file) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:$file"));
    $target = $tmp . '/baseline/' . substr($file, strlen('expansa-cms/expansa/Assets/'));
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('Expansa\Assets', 'AssetsBaseline', $source));
    touch($target, time() - 60);
}
spl_autoload_register(function (string $class) use ($tmp) {
    if (str_starts_with($class, 'AssetsBaseline\\')) {
        require $tmp . '/baseline/' . str_replace('\\', '/', substr($class, 15)) . '.php';
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

// 10 local styles and 10 local scripts with dependencies, rendered as a page does
@mkdir("$tmp/site/assets", 0777, true);
Url::configure("$tmp/site");
$enqueue = function (object $manager): void {
    for ($i = 0; $i < 10; $i++) {
        file_put_contents(Url::toPath("assets/s$i.css"), "body { color : red; } /* $i */");
        file_put_contents(Url::toPath("assets/s$i.js"), "var a$i = $i;");
        $manager->style("s$i", "/assets/s$i.css", ['dependencies' => $i ? ['s' . ($i - 1)] : []]);
        $manager->script("s$i", "/assets/s$i.js", ['data' => ['i' => $i]]);
    }
};

$old = new AssetsBaseline\Manager();
$new = new Manager();
$enqueue($old);
$enqueue($new);

$render = fn (object $manager, array $args) => function () use ($manager, $args) {
    ob_start();
    $manager->render(...$args);
    ob_end_clean();
};

$cases = [
    'render'         => [$render($old, []), $render($new, [])],
    'minify'         => [$render($old, ['minify' => true]), $render($new, ['minify' => true])],
    'combine inline' => [$render($old, ['combine' => true, 'inline' => true]), $render($new, ['combine' => true, 'inline' => true])],
];

printf("%-16s %12s %12s %8s\n", 'case', "$ref, µs", 'current, µs', 'speedup');
foreach ($cases as $name => [$baseline, $current]) {
    $a = measure($baseline, $iterations);
    $b = measure($current, $iterations);
    printf("%-16s %12.2f %12.2f %7.1fx\n", $name, $a, $b, $a / $b);
}
