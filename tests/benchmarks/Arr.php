<?php

declare(strict_types=1);

use Expansa\Support\Arr;

// run: php tests/benchmarks/Arr.php [--baseline=<git ref or file> ...] [--iterations=N]
// every baseline, the Arr of a commit or a file, is measured on the same input; speedup is against the first one
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 20000);
$baselines  = [];

foreach ((array) ($options['baseline'] ?? []) as $n => $ref) {
    if (is_file($ref)) {
        $source = file_get_contents($ref);
    } else {
        $object = escapeshellarg($ref . ':expansa-cms/expansa/Support/Arr.php');
        $source = shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . ' show ' . $object . ' 2>&1');
    }
    if (! is_string($source) || ! str_starts_with($source, '<?php')) {
        fwrite(STDERR, "Cannot load the baseline $ref: $source" . PHP_EOL);
        exit(1);
    }
    // eval() and fresh files (opcache.file_update_protection) skip opcache, the baseline would look slower
    $file = tempnam(sys_get_temp_dir(), 'arr');
    file_put_contents($file, preg_replace('/^class Arr\b/m', "class ArrBaseline$n", $source, 1));
    touch($file, time() - 60);
    require $file;
    unlink($file);
    $baselines[ 'Expansa\Support\ArrBaseline' . $n ] = pathinfo($ref, PATHINFO_FILENAME);
}

$list  = [];
$flat  = [];
$tree  = [];
$dots  = [];
$attrs = [
    'type'        => 'text',
    'name'        => 'user[name]',
    'class'       => 'field',
    'id'          => '',
    'value'       => 'Kevin "K" Malone',
    'required'    => true,
    'disabled'    => false,
    'u-model'     => 'name',
    'placeholder' => 'Name',
];

for ($i = 0; $i < 100; $i++) {
    $list[ "item$i" ] = ['type' => $i % 3 ? 'post' : 'page', 'public' => $i % 2 === 0, 'position' => 100 - $i];
    $flat[ "key$i" ]  = $i % 4 ? "value $i" : '';
}
for ($i = 0; $i < 10; $i++) {
    for ($j = 0; $j < 10; $j++) {
        $tree[ "group$i" ][ "section$j" ] = ['value' => $j, 'list' => [1, 0, ''], 'empty' => []];
        $dots[ "group$i.section$j.value" ]  = $j;
    }
}

$pattern = ['key90', 'key5', 'missing', 'key50'];
$keys    = ['key1', 'key20', 'key30', 'key99', 'missing'];
$nested  = ['menu' => ['sub' => $flat]];

$cases = [
    'clean'           => fn (string $c) => $c::clean($tree),
    'exclude'         => fn (string $c) => $c::exclude($flat, $keys),
    'extract'         => fn (string $c) => $c::extract($flat, $keys),
    'insert'          => fn (string $c) => $c::insert($flat, 'key50', ['new' => 1]),
    // the baseline has no "dot" support and returns the array untouched, so its time is not comparable
    'insert (dot)'    => fn (string $c) => $c::insert($nested, 'menu.sub.key50', ['new' => 1]),
    'sortByPattern'   => fn (string $c) => $c::sortByPattern($flat, $pattern),
    'sort'            => fn (string $c) => $c::sort($list, 'position'),
    'map'             => fn (string $c) => $c::map($tree, 'strval'),
    'filter AND'      => fn (string $c) => $c::filter($list, ['type' => 'post', 'public' => true]),
    'filter OR'       => fn (string $c) => $c::filter($list, ['type' => 'page', 'public' => true], 'OR'),
    'filter NOT'      => fn (string $c) => $c::filter($list, ['type' => 'page', 'public' => true], 'NOT'),
    'toHtmlAtts'      => fn (string $c) => $c::toHtmlAtts($attrs),
    'dot'             => fn (string $c) => $c::dot($tree),
    'undot'           => fn (string $c) => $c::undot($dots),
    'set'             => function (string $c) use ($tree) {
        $c::set($tree, 'group5.section5.value', 1);
    },
    'set (plain key)' => function (string $c) use ($flat) {
        $c::set($flat, 'key5', 1);
    },
    'get'             => fn (string $c) => $c::get($tree, 'group5.section5.value'),
    'get (plain key)' => fn (string $c) => $c::get($flat, 'key50'),
    'get (missing)'   => fn (string $c) => $c::get($tree, 'group5.missing.value', 0),
];

/**
 * Average time of one call per class in nanoseconds, the best of the rounds.
 * Rounds alternate between the classes, so a drift of the machine affects them equally.
 *
 * @param callable $case
 * @param string[] $classes
 * @param int      $iterations
 * @param int      $rounds
 * @return array<string, float|null> null when the class fails on the case
 */
function measure(callable $case, array $classes, int $iterations, int $rounds = 5): array
{
    $best = [];
    foreach ($classes as $class) {
        try {
            $case($class);
            $best[ $class ] = INF;
        } catch (Throwable) {
            $best[ $class ] = null;
        }
    }

    for ($round = 0; $round < $rounds; $round++) {
        foreach ($classes as $class) {
            if ($best[ $class ] === null) {
                continue;
            }
            $start = hrtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $case($class);
            }
            $best[ $class ] = min($best[ $class ], (hrtime(true) - $start) / $iterations);
        }
    }

    return $best;
}

$opcache = function_exists('opcache_get_status') && opcache_get_status() ? 'on' : 'off';
printf("PHP %s, opcache %s, %d iterations, time of one call in ns\n\n", PHP_VERSION, $opcache, $iterations);

$classes = [...array_keys($baselines), Arr::class];
$first   = array_key_first($baselines);

printf('%-16s', 'method');
foreach ([...$baselines, 'current'] as $label) {
    printf(' %12s', substr($label, 0, 12));
}
echo $first ? sprintf(" %9s\n", 'speedup') : PHP_EOL;

foreach ($cases as $name => $case) {
    $time = measure($case, $classes, $iterations);

    printf('%-16s', $name);
    foreach ($time as $ns) {
        printf(' %12s', $ns === null ? '—' : sprintf('%.1f', $ns));
    }
    if ($first) {
        $before = $time[ $first ];
        printf(" %9s", $before === null ? '—' : sprintf('x%.2f', $before / $time[ Arr::class ]));
    }
    echo PHP_EOL;
}
