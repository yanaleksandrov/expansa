<?php

declare(strict_types=1);

use Expansa\Security\Markup;

// run: php tests/benchmarks/Markup.php [--baseline=<git ref or file>] [--iterations=N]
// the baseline is Markup of a commit or the Kses filter it replaced (312b293), loaded as SecurityBaseline\Filter
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 1000);
$ref        = $options['baseline'] ?? 'HEAD';

$source = is_file($ref) ? file_get_contents($ref) : null;
foreach (is_file($ref) ? [] : ['Markup', 'Kses'] as $class) {
    $object = escapeshellarg("$ref:expansa-cms/expansa/Security/$class.php");
    $source = shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . " show $object 2>&1");
    if (is_string($source) && str_starts_with($source, '<?php')) {
        break;
    }
}
if (! is_string($source) || ! str_starts_with($source, '<?php')) {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// eval() and fresh files (opcache.file_update_protection) skip opcache, the baseline would look slower
$file   = tempnam(sys_get_temp_dir(), 'markup');
$source = preg_replace('/^namespace [^;]+;/m', "namespace SecurityBaseline;\nuse Expansa\\Security\\Sanitizer;", $source, 1);
file_put_contents($file, preg_replace('/^(final )?class (Markup|Kses)\b/m', 'class Filter', $source, 1));
touch($file, time() - 60);
require $file;
unlink($file);

$baseline = new SecurityBaseline\Filter();
$filter   = method_exists($baseline, 'sanitize') ? $baseline->sanitize(...) : $baseline->apply(...);

$paragraph = '<p>Lorem ipsum <strong>dolor</strong> sit amet, <a href="https://example.com/page?a=1&amp;b=2" title="Example" '
    . 'target="_blank" rel="noopener">consectetur</a> adipiscing elit &mdash; <em>sed do</em> eiusmod tempor.</p>' . "\n";
$table = '<table class="data"><tr><td colspan=2 class="c">Cell</td><td>Other</td></tr></table>' . "\n";
$attack = '<script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:alert(1)">x</a>'
    . '<a href="java&#115;cript&#58;alert(1)" onclick="x()">y</a><div style="x" class=a id=b>z</div>' . "\n";

$inputs = [
    'plain text'      => str_repeat('Just a line of plain text without any markup. ', 20),
    'text & entities' => str_repeat('Tom &amp; Jerry &mdash; AT&T, 5 &lt; 6. ', 20),
    'post, 5 KB'      => str_repeat($paragraph, 25),
    'post, 100 KB'    => str_repeat($paragraph, 500),
    'table'           => str_repeat($table, 20),
    'attack'          => str_repeat($attack, 20),
    'unique links'    => implode('', array_map(
        fn (int $i) => "<p><a href=\"https://example.com/post-$i\" class=\"link-$i\" rel=\"noopener\">Post $i</a> text</p>\n",
        range(1, 25)
    )),
    'short title'     => 'Hello <b>world</b>',
];

/**
 * Time of one call in µs: the best of the rounds after a warm-up.
 */
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

// one filter per variant, as Sanitizer::markup() keeps one for the request
$markup = new Markup();

printf("%-16s %12s %12s %8s\n", 'input', 'baseline, µs', 'current, µs', 'diff');
foreach ($inputs as $name => $html) {
    $count = str_contains($name, '100 KB') ? max(1, intdiv($iterations, 20)) : $iterations;

    // rounds alternate, so that a busy machine slows both variants alike
    [$a, $b] = [INF, INF];
    for ($round = 0; $round < 5; $round++) {
        $a = min($a, measure(fn () => $filter($html), $count, 1));
        $b = min($b, measure(fn () => $markup->sanitize($html), $count, 1));
    }
    printf("%-16s %12.2f %12.2f %+7.0f%%\n", $name, $a, $b, ($b - $a) / $a * 100);
}
