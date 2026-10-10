<?php

declare(strict_types=1);

use Expansa\Builders\Tree;

// run: php tests/benchmarks/Builders.php [--baseline=<git ref or file> ...] [--iterations=N]
// Tree: nesting of the same items (menu, comments) in every baseline; speedup is against the first one
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 20000);
$baselines  = [];

foreach ((array) ($options['baseline'] ?? []) as $n => $ref) {
    if (is_file($ref)) {
        $source = file_get_contents($ref);
    } else {
        $object = escapeshellarg($ref . ':expansa-cms/expansa/Builders/Tree.php');
        $source = shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . ' show ' . $object . ' 2>&1');
    }
    if (! is_string($source) || ! str_starts_with($source, '<?php')) {
        fwrite(STDERR, "Cannot load the baseline $ref: $source" . PHP_EOL);
        exit(1);
    }
    // eval() and fresh files (opcache.file_update_protection) skip opcache, the baseline would look slower
    $file = tempnam(sys_get_temp_dir(), 'tree');
    // `new Tree()` and `: Tree` inside the class would create and expect the current Tree
    file_put_contents($file, preg_replace('/\bTree\b/', "TreeBaseline$n", $source));
    touch($file, time() - 60);
    require $file;
    unlink($file);
    $baselines['Expansa\Builders\TreeBaseline' . $n] = pathinfo($ref, PATHINFO_FILENAME);
}

/**
 * Items of a tree: the first ten at the top, every next one under an earlier item.
 *
 * @param int $count
 * @return array<int, array<string, mixed>>
 */
function items(int $count): array
{
    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $parent  = $i > 10 ? 'c' . intdiv($i, 3) : '';
        $items[] = ['id' => "c$i", 'parent_id' => $parent, 'title' => "Item $i", 'position' => $i % 7];
    }

    return $items;
}

/**
 * Nested items of a tree in a class of any version: get(), nested() or render().
 *
 * @param string $class
 * @param string $name
 * @return array
 */
function nested(string $class, string $name): array
{
    if (method_exists($class, 'walk')) {
        return $class::get($name);
    }

    $nested = [];
    $class::render($name, function (array $items) use (&$nested) {
        $nested = $items;
    });

    return $nested;
}

$sizes = ['menu, 50 items' => 50, 'comments, 1000' => 1000, 'comments, 5000' => 5000];

/**
 * Average time of one nesting per class in microseconds, the best of the rounds.
 * Rounds alternate between the classes, so a drift of the machine affects them equally.
 *
 * @param string[] $classes
 * @param string   $name
 * @param int      $iterations
 * @param int      $rounds
 * @return array<string, float>
 */
function measure(array $classes, string $name, int $iterations, int $rounds = 3): array
{
    $best = array_fill_keys($classes, INF);

    for ($round = 0; $round < $rounds; $round++) {
        foreach ($classes as $class) {
            $start = hrtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                nested($class, $name);
            }
            $best[$class] = min($best[$class], (hrtime(true) - $start) / $iterations / 1000);
        }
    }

    return $best;
}

$classes = [...array_keys($baselines), Tree::class];
$first   = array_key_first($baselines);

foreach ($sizes as $label => $count) {
    $items = items($count);
    foreach ($classes as $class) {
        $class::attach($label, fn ($tree) => method_exists($tree, 'append') ? $tree->append($items) : $tree->addItems($items));
    }
    // older versions give arrays and leave out `children` of a leaf, newer ones give Item objects
    $plain = function (array $items) use (&$plain): array {
        $list = [];
        foreach ($items as $item) {
            if (is_object($item)) {
                $item = [...$item->data, 'depth' => $item->depth, 'children' => $item->children];
            }
            $item['children'] = $plain($item['children'] ?? []);
            if ($item['children'] === []) {
                unset($item['children']);
            }
            ksort($item);
            $list[] = $item;
        }

        return $list;
    };
    $same = array_unique(array_map(fn (string $class) => md5(serialize($plain(nested($class, $label)))), $classes));
    if (count($same) > 1) {
        fwrite(STDERR, "The versions nest \"$label\" differently" . PHP_EOL);
        exit(1);
    }
}

$opcache = function_exists('opcache_get_status') && opcache_get_status() ? 'on' : 'off';
printf("PHP %s, opcache %s, time of one nesting in µs\n\n", PHP_VERSION, $opcache);

printf('%-16s', 'tree');
foreach ([...$baselines, 'current'] as $label) {
    printf(' %12s', substr($label, 0, 12));
}
echo $first ? sprintf(" %9s\n", 'speedup') : PHP_EOL;

foreach ($sizes as $label => $count) {
    // the same total work per tree, at least a couple of runs for the large ones
    $time = measure($classes, $label, max(2, intdiv($iterations, $count)));

    printf('%-16s', $label);
    foreach ($time as $us) {
        printf(' %12.1f', $us);
    }
    if ($first) {
        printf(" %9s", sprintf('x%.1f', $time[$first] / $time[Tree::class]));
    }
    echo PHP_EOL;
}
