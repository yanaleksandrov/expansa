<?php

declare(strict_types=1);

// run: php tests/benchmarks/Translation.php [--baseline=<git ref>] [--iterations=N]
// the baseline is the Translation package of a commit, loaded under the TranslationBaseline namespace
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 100000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-translation-bench-' . getmypid();

$files = shell_exec('git -C ' . escapeshellarg($root) . ' ls-tree -r --name-only ' . escapeshellarg($ref) . ' expansa-cms/expansa/Translation');
if (! is_string($files) || trim($files) === '') {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach (explode("\n", trim($files)) as $file) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:$file"));
    $target = $tmp . '/' . substr($file, strlen('expansa-cms/expansa/Translation/'));
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('Expansa\Translation', 'TranslationBaseline', $source));
    touch($target, time() - 60);
}
spl_autoload_register(function (string $class) use ($tmp) {
    if (str_starts_with($class, 'TranslationBaseline\\')) {
        require $tmp . '/' . str_replace('\\', '/', substr($class, 20)) . '.php';
    }
});

// the entry point was Translator before it became Manager
$baselineClass = is_file("$tmp/Manager.php") ? 'TranslationBaseline\Manager' : 'TranslationBaseline\Translator';
$currentClass  = class_exists('Expansa\Translation\Manager') ? 'Expansa\Translation\Manager' : 'Expansa\Translation\Translator';

$old = new $baselineClass();
$new = new $currentClass();

// the baseline filtered its two built-in languages, the current version gets the bundled list
$bundled = Expansa\Translation\Languages::all();
$filter  = fn (?array $languages = null) => $languages ?? $bundled;
$old->configure(routes: [], pattern: 'i18n/%s', languages: $filter);
$new->configure(routes: [], pattern: 'i18n/%s', languages: $filter);

function measure(callable $callback, int $iterations, int $rounds = 5): float
{
    $callback();
    $best = INF;
    for ($r = 0; $r < $rounds; $r++) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }
        $best = min($best, (hrtime(true) - $start) / $iterations / 1000);
    }

    return $best;
}

$cases = [
    'plain'        => fn ($t) => $t->translate('Save changes'),
    'cyrillic'     => fn ($t) => $t->translate('Сохранить изменения'),
    'placeholders' => fn ($t) => $t->translate('Hi, ::Firstname, you have :count tasks', 'john', 3),
    'markdown'     => fn ($t) => $t->translate('See the [documentation](:link) **now**', 'https://example.com'),
    'attribute'    => fn ($t) => $t->translateAttribute('Search "posts" & pages'),
    'language'     => fn ($t) => $t->language('ru', 'iso_639_1'),
];

// forms of the whole string in older versions, a block of forms in braces now
if (method_exists($new, 'translatePlural')) {
    $before = method_exists($old, 'translatePlural') ? measure(fn () => $old->translatePlural(':count file|:count files', 5), $iterations) : null;
    $after  = measure(fn () => $new->translatePlural(':count {file|files}', 5), $iterations);
    printf("%-14s %12s %12.3f %8s\n", 'plural', $before === null ? '—' : sprintf('%.3f', $before), $after, $before === null ? '' : sprintf('%+.0f%%', ($after / $before - 1) * 100));
}

printf("%-14s %12s %12s %8s\n", 'case', "$ref, µs", 'current, µs', 'diff');
foreach ($cases as $name => $case) {
    $a = measure(fn () => $case($old), $iterations);
    $b = measure(fn () => $case($new), $iterations);
    printf("%-14s %12.3f %12.3f %7.0f%%\n", $name, $a, $b, ($b - $a) / $a * 100);
}

array_map('unlink', glob("$tmp/*/*.php") ?: []);
array_map('unlink', glob("$tmp/*.php") ?: []);
array_map('rmdir', glob("$tmp/*", GLOB_ONLYDIR) ?: []);
@rmdir($tmp);
