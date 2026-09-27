<?php

declare(strict_types=1);

// run: php tests/benchmarks/View.php [--baseline=<git ref>] [--iterations=N]
// the baseline is the View package of a commit, loaded under the ViewBaseline namespace
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';
require_once EX_PATH . 'expansa/functions.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 5000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-view-bench-' . getmypid();

$files = shell_exec('git -C ' . escapeshellarg($root) . ' ls-tree -r --name-only ' . escapeshellarg($ref) . ' expansa-cms/expansa/View');
if (! is_string($files) || trim($files) === '') {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach (explode("\n", trim($files)) as $file) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:$file"));
    $target = $tmp . '/baseline/' . substr($file, strlen('expansa-cms/expansa/View/'));
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('Expansa\View', 'ViewBaseline', $source));
    touch($target, time() - 60);
}
spl_autoload_register(function (string $class) use ($tmp) {
    $file = $tmp . '/baseline/' . str_replace('\\', '/', substr($class, 13)) . '.php';
    if (str_starts_with($class, 'ViewBaseline\\') && is_file($file)) {
        require $file;
    }
});

$views = [
    'page.php'       => '<ul><?php foreach ($items as $item): ?><li><?php echo escape($item); ?></li><?php endforeach; ?></ul>',
    'row.blade.php'  => '<tr>@if ($item !== \'\')<td>{{ $item }}</td>@else<td>-</td>@endif</tr>',
    'list.blade.php' => '<table>@foreach ($items as $item)@include(\'row\', [\'item\' => $item])@endforeach</table>',
];
@mkdir("$tmp/views", 0777, true);
foreach ($views as $name => $source) {
    file_put_contents("$tmp/views/$name", $source);
    touch("$tmp/views/$name", time() - 60);
}

// the package before the rename had Factory::make(), now Manager::create()
function renderer(string $namespace, string $views, string $cache): Closure
{
    if (class_exists("$namespace\\Manager")) {
        $manager = new ("$namespace\\Manager")();
        $manager->configure($views, $cache);

        return fn (string $view, array $data) => $manager->create($view, $data)->render();
    }

    $factory = new ("$namespace\\Factory")(new ("$namespace\\Finder")($views), new ("$namespace\\Engines\\EngineManager")(), ['cache' => true, 'cache_path' => $cache]);

    return fn (string $view, array $data) => $factory->make($view, $data)->render();
}

function measure(callable $callback, int $iterations, int $rounds = 5): float
{
    $callback();
    $best = INF;
    for ($round = 0; $round < $rounds; $round++) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }
        $best = min($best, (hrtime(true) - $start) / $iterations / 1000);
    }

    return $best;
}

$old   = renderer('ViewBaseline', "$tmp/views", "$tmp/cache-old");
$new   = renderer('Expansa\View', "$tmp/views", "$tmp/cache-new");
$items = array_map(fn (int $i) => "Item <$i>", range(1, 20));

$cases = [
    'php view'        => ['page', ['items' => $items]],
    'blade view'      => ['row', ['item' => 'Item']],
    'blade, 20 rows'  => ['list', ['items' => $items]],
];

printf("%-16s %12s %12s %8s %s\n", 'case', "$ref, µs", 'current, µs', 'speedup', 'same output');
foreach ($cases as $name => [$view, $data]) {
    $a = measure(fn () => $old($view, $data), $iterations);
    $b = measure(fn () => $new($view, $data), $iterations);
    printf("%-16s %12.2f %12.2f %7.1fx %s\n", $name, $a, $b, $a / $b, $old($view, $data) === $new($view, $data) ? 'yes' : 'NO');
}
