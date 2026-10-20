<?php

declare(strict_types=1);

// run: php tests/benchmarks/Templates.php [--baseline=<git ref>] [--iterations=N]
// the dashboard templates of a commit and of the working tree render the same data with the current View package
const EX_PATH      = __DIR__ . '/../../expansa-cms/';
const EX_DASHBOARD = EX_PATH . 'dashboard/';

require_once EX_PATH . 'autoload.php';
require_once EX_PATH . 'expansa/functions.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 2000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-templates-bench-' . getmypid();
$templates  = ['parts/table/cell-badge', 'parts/table/cell-plugin', 'parts/table/table-header', 'parts/state', 'parts/rating'];

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach ($templates as $template) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:expansa-cms/dashboard/views/$template.blade.php"));
    if (! is_string($source) || ! str_starts_with($source, '<?php')) {
        fwrite(STDERR, "Cannot load $template of $ref" . PHP_EOL);
        exit(1);
    }
    @mkdir(dirname("$tmp/views/$template"), 0777, true);
    file_put_contents("$tmp/views/$template.blade.php", $source);
    touch("$tmp/views/$template.blade.php", time() - 60);
}

$old = new Expansa\View\Manager();
$old->configure(["$tmp/views", EX_DASHBOARD . 'views'], "$tmp/cache-old");
$new = new Expansa\View\Manager();
$new->configure([EX_DASHBOARD . 'views'], "$tmp/cache-new");

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

// a page of 50 rows, as Posts and Plugins build them
$rows = [];
for ($i = 0; $i < 50; $i++) {
    $rows[] = [
        'title'  => "Plugin <$i>",
        'author' => 'Expansa',
        'status' => [
            'class'      => $i % 2 ? 'badge t-green' : '',
            'value'      => $i % 2 ? 'Published' : 'Draft',
            'attributes' => ['u-bind' => 'rowAction', 'data-endpoint' => 'extensions/activate', 'data-payload' => '{"id":"seo"}'],
        ],
    ];
}

$cases = [
    'cell-badge × 50'  => fn (Expansa\View\Manager $view) => implode('', array_map(fn (array $row) => $view->create('parts/table/cell-badge', ['cell' => null, 'item' => $row, 'value' => $row['status']])->render(), $rows)),
    'cell-plugin × 50' => fn (Expansa\View\Manager $view) => implode('', array_map(fn (array $row) => $view->create('parts/table/cell-plugin', ['cell' => null, 'item' => $row, 'value' => $row['title']])->render(), $rows)),
    'table-header'     => fn (Expansa\View\Manager $view) => $view->create('parts/table/table-header', ['title' => 'Users', 'create' => 'Add New User', 'link' => '/dashboard/profile?new', 'query' => []])->render(),
    'state'            => fn (Expansa\View\Manager $view) => $view->create('parts/state', ['icon' => 'no-plugins', 'title' => 'No plugins', 'description' => 'Upload them.', 'buttons' => [['label' => 'Add', 'url' => '/add']]])->render(),
    'rating'           => fn (Expansa\View\Manager $view) => $view->create('parts/rating', ['rating' => 4.4, 'reviews' => 12])->render(),
];

printf("%-17s %12s %12s %8s %s\n", 'case', "$ref, µs", 'current, µs', 'speedup', 'same output');
foreach ($cases as $name => $render) {
    $a = measure(fn () => $render($old), $iterations);
    $b = measure(fn () => $render($new), $iterations);
    // whitespace is not compared: git gives LF, the working tree may have CRLF
    $same = preg_replace('/\s+/', ' ', $render($old)) === preg_replace('/\s+/', ' ', $render($new));
    printf("%-17s %12.2f %12.2f %7.1fx %s\n", $name, $a, $b, $a / $b, $same ? 'yes' : 'NO');
}
