<?php

declare(strict_types=1);

use Expansa\Http\Request;

// run: php tests/benchmarks/Http.php [--baseline=<git ref>] [--iterations=N]
// the baseline is the Http package of a commit, loaded under the HttpBaseline namespace
const EX_PATH = __DIR__ . '/../../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$options    = getopt('', ['baseline:', 'iterations:']);
$iterations = (int) ($options['iterations'] ?? 50000);
$ref        = $options['baseline'] ?? 'HEAD';
$root       = dirname(__DIR__, 2);
$tmp        = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-http-bench-' . getmypid();

$files = shell_exec('git -C ' . escapeshellarg($root) . ' ls-tree -r --name-only ' . escapeshellarg($ref) . ' expansa-cms/expansa/Http');
if (! is_string($files) || trim($files) === '') {
    fwrite(STDERR, "Cannot load the baseline $ref" . PHP_EOL);
    exit(1);
}

// fresh files skip opcache (opcache.file_update_protection), the baseline would look slower
foreach (explode("\n", trim($files)) as $file) {
    $source = shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg("$ref:$file"));
    $target = $tmp . '/baseline/' . substr($file, strlen('expansa-cms/expansa/Http/'));
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('Expansa\Http', 'HttpBaseline', $source));
    touch($target, time() - 60);
}
spl_autoload_register(function (string $class) use ($tmp) {
    if (str_starts_with($class, 'HttpBaseline\\')) {
        require $tmp . '/baseline/' . str_replace('\\', '/', substr($class, 13)) . '.php';
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

// a typical API call: a form post from the dashboard
$_GET    = [];
$_POST   = ['page' => '2', 's' => 'cat', 'ids' => ['1', '2', '3']];
$_COOKIE = ['expansa_auth' => str_repeat('a', 64), 'x_csrf_token' => str_repeat('b', 40)];
$_SERVER = [
    'REQUEST_METHOD'       => 'POST',
    'REQUEST_URI'          => '/api/media/get',
    'QUERY_STRING'         => '',
    'HTTP_HOST'            => 'example.com',
    'HTTP_USER_AGENT'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'HTTP_ACCEPT'          => 'application/json',
    'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
    'HTTP_X_CSRF_TOKEN'    => str_repeat('b', 40),
    'CONTENT_TYPE'         => 'application/x-www-form-urlencoded',
    'CONTENT_LENGTH'       => '30',
    'SERVER_NAME'          => 'example.com',
    'SERVER_PORT'          => '443',
    'HTTPS'                => 'on',
    'REMOTE_ADDR'          => '127.0.0.1',
];

// the Request API before: post() and all() methods; all() failed on the uninitialized FileBag, so its sources are merged here
$cases = [
    'form value' => [
        fn () => HttpBaseline\Request::createFromGlobals()->post('s', ''),
        fn () => Request::createFromGlobals()->post['s'] ?? '',
    ],
    'all input'  => [
        function () {
            $request = HttpBaseline\Request::createFromGlobals();

            return array_merge($request->query(), $request->post(), $request->json());
        },
        fn () => Request::createFromGlobals()->input,
    ],
    'header'     => [
        fn () => HttpBaseline\Request::createFromGlobals()->header('X-Csrf-Token'),
        fn () => Request::createFromGlobals()->getHeader('X-Csrf-Token'),
    ],
];

printf("%-12s %12s %12s %8s\n", 'case', "$ref, µs", 'current, µs', 'speedup');
foreach ($cases as $name => [$baseline, $current]) {
    $a = measure($baseline, $iterations);
    $b = measure($current, $iterations);
    printf("%-12s %12.2f %12.2f %7.1fx\n", $name, $a, $b, $a / $b);
}
