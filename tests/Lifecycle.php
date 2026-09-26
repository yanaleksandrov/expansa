<?php

declare(strict_types=1);

use Expansa\Facades\Hook;
use Expansa\Lifecycle\Exceptions\LifecycleException;
use Expansa\Lifecycle\Manager;

// run: php tests/Lifecycle.php
require_once __DIR__ . '/bootstrap.php';

$log = [];
Hook::add('beforeBoot', function () use (&$log) { $log[] = 'hook:beforeBoot'; });
Hook::add('afterRegister', function () use (&$log) { $log[] = 'hook:afterRegister'; });
Hook::add('enterDashboard', function () use (&$log) { $log[] = 'hook:enterDashboard'; });

$app = new Manager();
$app->phase('boot', true, function () use (&$log) { $log[] = 'boot'; })
    ->phase('register', true, function () use (&$log) { $log[] = 'register'; })
    ->context('api', fn (string $uri) => str_starts_with($uri, '/api/'), function () use (&$log) { $log[] = 'api'; })
    ->context('dashboard', fn (string $uri) => str_starts_with($uri, '/dashboard'), function () use (&$log) { $log[] = 'dashboard'; })
    ->context('web', fn () => true, function () use (&$log) { $log[] = 'web'; });
$app->run('/dashboard/chat');

check('phases and the first matching context run in order with hooks', $log === [
    'hook:beforeBoot', 'boot', 'register', 'hook:afterRegister', 'dashboard', 'hook:enterDashboard',
]);
check('current context is reported', $app->current() === 'dashboard' && $app->is('dashboard') && !$app->is('web'));
check('timeline records every executed step, no routing in the console', array_column($app->timeline(), 'name') === ['boot', 'register', 'dashboard']);
check('second run is rejected', throws(fn () => $app->run('/'), LifecycleException::class));
check('declaring after start is rejected', throws(fn () => $app->phase('late', true, fn () => null), LifecycleException::class));
check('duplicate phase is rejected', throws(fn () => new Manager()->phase('boot', true, fn () => null)->phase('boot', true, fn () => null), LifecycleException::class));

$log = [];
Hook::add('beforeSkipped', function () use (&$log) { $log[] = 'hook:beforeSkipped'; });

$app = new Manager();
$app->phase('skipped', fn () => false, function () use (&$log) { $log[] = 'skipped'; })
    ->phase('kept', fn () => true, function () use (&$log) { $log[] = 'kept'; });
$app->run('/');
check('phase with false $when is skipped without hooks and timeline entry', $log === ['kept'] && array_column($app->timeline(), 'name') === ['kept']);

$log = [];
$app = new Manager();
$app->phase('skipped', false, function () use (&$log) { $log[] = 'skipped'; })
    ->phase('kept', true, function () use (&$log) { $log[] = 'kept'; })
    ->context('never', false, function () use (&$log) { $log[] = 'never'; })
    ->context('always', true, function () use (&$log) { $log[] = 'always'; });
$app->run('/');
check('bool $when works for phases and contexts', $log === ['kept', 'always'] && $app->current() === 'always');

$seen = [];
$app  = new Manager();
$app->phase('extensions', true, function () use (&$app, &$seen) { $seen[] = $app->is('dashboard'); })
    ->context('api', fn (string $uri) => str_starts_with($uri, '/api/'), fn () => null)
    ->context('dashboard', fn (string $uri) => str_starts_with($uri, '/dashboard'), function () use (&$seen) { $seen[] = 'dashboard'; });
$seen[] = $app->current();
$app->run('/dashboard/chat');
check('context is known inside a phase, not memoized before run()', $seen === [null, true, 'dashboard']);

$app = new Manager();
$app->context('api', fn (string $uri) => str_starts_with($uri, '/api/'), fn () => null);
$app->run('/sign-in');
check('no matching context leaves current empty', $app->current() === null);

$app = new Manager();
$app->phase('boot', true, fn () => throw new RuntimeException('boom'));
try {
    $app->run('/');
} catch (RuntimeException) {
}
check('failed step is still recorded in timeline', array_column($app->timeline(), 'name') === ['boot']);

$caught = null;
$app    = new Manager();
$app->phase('boot', true, fn () => throw new RuntimeException('boom'))
    ->phase('register', true, function () use (&$log) { $log[] = 'register after error'; });
$app->run('/', function (Throwable $e) use (&$caught) { $caught = $e->getMessage(); });
check('catch receives the error and stops the remaining steps', $caught === 'boom' && !in_array('register after error', $log, true));

// terminate runs at shutdown, so it is checked in a child process that exits from inside a phase
$child = <<<'PHP'
    <?php
    const EX_PATH = %s;
    require_once EX_PATH . 'autoload.php';
    Expansa\Facades\Hook::add('terminate', function () { echo 'terminate'; });
    $app = new Expansa\Lifecycle\Manager();
    $app->phase('boot', true, function () { echo 'boot,'; exit; });
    $app->run('/');
    PHP;
$file = tempnam(sys_get_temp_dir(), 'lifecycle');
file_put_contents($file, sprintf($child, var_export(EX_PATH, true)));
$output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file));
unlink($file);
check('terminate hook runs after the response, even after exit', $output === 'boot,terminate');

exit($failures > 0 ? 1 : 0);
