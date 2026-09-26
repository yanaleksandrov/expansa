<?php

declare(strict_types=1);

use Expansa\Routing\Router;

// run: php tests/Routing.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';
require_once EX_PATH . 'expansa/functions.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

/**
 * A router for one request; its base path is computed once per instance.
 */
function request(string $method, string $uri, string $script = '/index.php'): Router
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI']    = $uri;
    $_SERVER['SCRIPT_NAME']    = $script;

    return new Router();
}

final class ArticlesController
{
    public function index(): void {}

    public function show(): void {}

    public function signIn(): void {}
}

$log = [];

$router = request('GET', '/about');
$router->get('/about', function () use (&$log) { $log[] = 'about'; });
check('a static route runs its handler', $router->run() && $log === ['about']);

$router = request('GET', '/about?ref=mail');
$router->get('/about', fn () => null);
check('the query string does not affect matching', $router->uri() === '/about' && $router->run());

$seen   = null;
$router = request('GET', '/post/42');
$router->get('/post/(\d+)', function (string $id) use (&$seen) { $seen = $id; });
check('a dynamic route receives its parameter', $router->run() && $seen === '42');

$router = request('POST', '/about');
$router->get('/about', fn () => null);
$router->set404(fn () => null);
check('a route of another method does not match', ! $router->run());

$log    = [];
$router = request('GET', '/api/users');
$router->before('GET', '/api/.*', function () use (&$log) { $log[] = 'before'; });
$router->prefix('/api', function () use ($router, &$log) {
    $router->get('/users', function () use (&$log) { $log[] = 'users'; });
});
check('before middleware runs first, prefix() nests the route', $router->run() && $log === ['before', 'users']);

$router = request('GET', '/%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82-%E4%B8%96%E7%95%8C');
check('a percent-encoded URI is decoded, so Unicode slugs match', $router->uri() === '/привет-世界');

$router = request('GET', '/sub/about', '/sub/index.php');
check('the base path of an app in a subdirectory is removed', $router->uri() === '/about');

$calls  = [];
$router = request('GET', '/articles/7');
$router->register(ArticlesController::class, function (string $controller, string $method, array $params) use (&$calls) {
    $calls[] = "$method:" . implode(',', $params);
});
$router->run();
$router = request('POST', '/articles/sign-in');
$router->register(ArticlesController::class, function (string $controller, string $method) use (&$calls) {
    $calls[] = $method;
});
$router->run();
check('register() maps CRUD names to REST and other methods to POST /kebab-case', $calls === ['show:7', 'signIn']);

exit($failures > 0 ? 1 : 0);
