<?php

declare(strict_types=1);

use Expansa\Security\Xss\Kses;

// run: php tests/benchmarks/Kses.php [--baseline=<git ref or file>] [--iterations=N], the original kses is 4492a8a
// the baseline, Kses of a commit or a file, is loaded as KsesBaseline and measured on the same input
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 2000);
$ref        = $options['baseline'] ?? 'HEAD';

if (is_file($ref)) {
    $source = file_get_contents($ref);
} else {
    $object = escapeshellarg($ref . ':expansa-cms/expansa/Security/Xss/Kses.php');
    $source = shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . ' show ' . $object . ' 2>&1');
}
if (! is_string($source) || ! str_starts_with($source, '<?php')) {
    fwrite(STDERR, "Cannot load the baseline $ref: $source" . PHP_EOL);
    exit(1);
}

// eval() and fresh files (opcache.file_update_protection) skip opcache, the baseline would look slower
$file = tempnam(sys_get_temp_dir(), 'kses');
file_put_contents($file, preg_replace('/^(final )?class Kses\b/m', 'class KsesBaseline', $source, 1));
touch($file, time() - 60);
require $file;
unlink($file);

$paragraph = '<p>Lorem ipsum <strong>dolor</strong> sit amet, <a href="https://example.com/page?a=1&amp;b=2" title="Example" '
    . 'target="_blank" rel="noopener">consectetur</a> adipiscing elit &mdash; <em>sed do</em> eiusmod tempor.</p>' . "\n";
$table = '<table class="data" border="1" cellpadding="2"><tr><td colspan=2 class="c" width="50%">Cell</td>'
    . '<td align=center valign=top>Other</td></tr></table>' . "\n";
$attack = '<script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:alert(1)">x</a>'
    . '<a href="java&#115;cript&#58;alert(1)" onclick="x()">y</a><div style="x" class=a id=b>z</div>' . "\n";

$inputs = [
    'plain text'      => str_repeat('Just a line of plain text without any markup. ', 20),
    'text & entities' => str_repeat('Tom &amp; Jerry &mdash; AT&T, 5 &lt; 6. ', 20),
    'post, 5 KB'      => str_repeat($paragraph, 25),
    'table'           => str_repeat($table, 20),
    'attack'          => str_repeat($attack, 20),
    'unique links'    => implode('', array_map(
        fn (int $i) => "<p><a href=\"https://example.com/post-$i\" class=\"link-$i\" rel=\"noopener\">Post $i</a> text</p>\n",
        range(1, 25)
    )),
    'short title'     => 'Hello <b>world</b>',
];

function measure(callable $callback, int $iterations): float
{
    $callback();
    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }

    return (hrtime(true) - $start) / $iterations / 1000;
}

// the original kses gets the current rules, so only the speed of the algorithm is compared;
// a rewritten baseline has its own read-only rules
$rules    = new Kses()->allowedHtml;
$property = new ReflectionProperty(Expansa\Security\Xss\KsesBaseline::class, 'allowedHtml');
$settable = $property->isPublic() && ! $property->isReadOnly() && ! $property->isPrivateSet() && ! $property->hasHooks();
$baseline = function () use ($rules, $settable) {
    $kses = new Expansa\Security\Xss\KsesBaseline();
    if ($settable) {
        $kses->allowedHtml = $rules;
    }

    return $kses;
};
$old = fn (string $html) => $baseline()->apply($html);
$new = fn (string $html) => new Kses()->apply($html);

// Sanitizer::markup() created a filter per call, now it keeps one and its cache
$title      = $inputs['short title'];
$markupWarm = measure(fn () => Expansa\Security\Sanitizer::markup($title), $iterations * 10);

// cold: a new filter per call has an empty cache; warm: one filter filters the same text again
$warm = new Kses();

printf("%-16s %12s %12s %12s %8s %8s %s\n", 'input', "$ref, µs", 'cold, µs', 'warm, µs', 'cold', 'warm', 'same output');
foreach ($inputs as $name => $html) {
    $a = measure(fn () => $old($html), $iterations);
    $b = measure(fn () => $new($html), $iterations);
    $c = measure(fn () => $warm->apply($html), $iterations);
    printf("%-16s %12.2f %12.2f %12.2f %7.1fx %7.1fx %s\n", $name, $a, $b, $c, $a / $b, $a / $c, $old($html) === $new($html) ? 'yes' : 'NO');
}

$a = measure(fn () => $baseline()->apply($title), $iterations * 10);
printf("%-16s %12.2f %12s %12.2f %8s %7.1fx\n", 'markup() call', $a, '', $markupWarm, '', $a / $markupWarm);
