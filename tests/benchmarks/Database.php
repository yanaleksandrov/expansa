<?php

declare(strict_types=1);

use Expansa\Facades\Cache;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;

// run: php tests/benchmarks/Database.php [--baseline=<git ref>] [--iterations=N]
// the baseline is the Database package of a commit, loaded under the DatabaseBaseline namespace;
// both use the same Db connection in test mode: the SQL is built, nothing is sent
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';
require_once EX_PATH . 'expansa/functions.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 5000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-database-bench-' . getmypid();

$files = shell_exec('git -C ' . escapeshellarg($root) . ' ls-tree -r --name-only ' . escapeshellarg($ref) . ' expansa-cms/expansa/Database');
if (! is_string($files) || trim($files) === '') {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach (explode("\n", trim($files)) as $file) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:$file"));
    $target = $tmp . '/baseline/' . substr($file, strlen('expansa-cms/expansa/Database/'));
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('Expansa\Database', 'DatabaseBaseline', $source));
    touch($target, time() - 60);
}
spl_autoload_register(function (string $class) use ($tmp) {
    $file = $tmp . '/baseline/' . str_replace('\\', '/', substr($class, 17)) . '.php';
    if (str_starts_with($class, 'DatabaseBaseline\\') && is_file($file)) {
        require $file;
    }
});

Db::configure(driver: 'mysql', database: 'bench', username: '', password: '', host: '', testMode: true);

// the model API changed between versions, so each model is declared for the version it extends
function declareModel(string $name, string $namespace): string
{
    $sanitizing = trait_exists("$namespace\\Traits\\HasSanitizing") ? "$namespace\\Traits\\HasSanitizing" : "$namespace\\Model\\HasSanitizing";
    $public     = new ReflectionProperty("$namespace\\Model", 'table')->isPublic();
    $table      = $public ? 'public protected(set) string' : 'protected string';
    $fillable   = $public ? 'public protected(set) array' : 'protected array';

    eval(<<<PHP
        final class $name extends \\$namespace\\Model
        {
            use \\$sanitizing;

            $table \$table = 'posts';

            $fillable \$fillable = ['title', 'content', 'status', 'author_id'];

            protected function getSanitizerRules(): array
            {
                return ['title' => 'trim', 'status' => 'kebabcase', 'author_id' => 'absint'];
            }
        }
        PHP);

    return $name;
}

if (method_exists(Expansa\Database\Model::class, 'configure')) {
    Expansa\Database\Model::configure(
        cache: Cache::get(...),
        forgetCache: Cache::forget(...),
        sanitizer: fn (array $data, array $rules) => Safe::data($data, $rules)->apply(),
    );
}

$old = declareModel('BaselinePost', 'DatabaseBaseline');
$new = declareModel('CurrentPost', 'Expansa\Database');

$rows = [];
for ($i = 1; $i <= 20; $i++) {
    $rows[] = ['id' => $i, 'title' => "Post $i", 'content' => str_repeat('text ', 20), 'status' => 'publish', 'author_id' => 1, 'deleted_at' => null];
}
$input = ['title' => '  Hello  ', 'content' => 'Body', 'status' => 'Draft Post', 'author_id' => '7'];

$hydrate = fn (string $class) => method_exists($class, 'hydrate') ? $class::hydrate(...) : $class::make(...);

$cases = [
    'hydrate 20 rows' => fn (string $class) => fn () => array_map($hydrate($class), $rows),
    'read attributes' => function (string $class) use ($rows, $hydrate) {
        $models = array_map($hydrate($class), $rows);

        return function () use ($models) {
            foreach ($models as $model) {
                $model->title . $model->status . $model->author_id;
            }
        };
    },
    'fill + sanitize' => fn (string $class) => fn () => new $class($input),
    'get by id'       => fn (string $class) => fn () => $class::get(1),
    'select SQL'      => fn () => fn () => Db::select('posts', ['id', 'title'], ['status' => 'publish', 'id[>]' => 5, 'ORDER' => ['id' => 'DESC'], 'LIMIT' => 10]),
];

printf("%-16s %12s %12s %8s\n", 'case', "$ref, µs", 'current, µs', 'speedup');
foreach ($cases as $name => $factory) {
    // rounds alternate, so a drift of the machine affects both variants alike
    [$baseline, $current, $a, $b] = [$factory($old), $factory($new), INF, INF];
    for ($round = 0; $round < 5; $round++) {
        $a = min($a, measure($baseline, $iterations, 1));
        $b = min($b, measure($current, $iterations, 1));
    }
    printf("%-16s %12.2f %12.2f %7.2fx\n", $name, $a, $b, $a / $b);
}

/**
 * Best of the rounds after a warm-up, in microseconds per call.
 *
 * @param callable $callback
 * @param int      $iterations
 * @param int      $rounds
 * @return float
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
