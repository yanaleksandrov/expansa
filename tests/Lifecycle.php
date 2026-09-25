<?php

declare(strict_types=1);

use Expansa\Facades\Hook;
use Expansa\Lifecycle\Exception\LifecycleException;
use Expansa\Lifecycle\Manager;

// run: php tests/Lifecycle.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

function throws(callable $callback): bool
{
    try {
        $callback();
    } catch (LifecycleException) {
        return true;
    }

    return false;
}

$log = [];
Hook::add('beforeBoot', function () use (&$log) { $log[] = 'hook:beforeBoot'; });
Hook::add('afterRegister', function () use (&$log) { $log[] = 'hook:afterRegister'; });
Hook::add('enterDashboard', function () use (&$log) { $log[] = 'hook:enterDashboard'; });

$app = new Manager();
$app->phase('boot', function () use (&$log) { $log[] = 'boot'; })
    ->phase('register', function () use (&$log) { $log[] = 'register'; })
    ->context('api', fn (string $uri) => str_starts_with($uri, '/api/'), function () use (&$log) { $log[] = 'api'; })
    ->context('dashboard', fn (string $uri) => str_starts_with($uri, '/dashboard'), function () use (&$log) { $log[] = 'dashboard'; })
    ->context('web', fn () => true, function () use (&$log) { $log[] = 'web'; })
    ->fallback(function () use (&$log) { $log[] = 'fallback'; });
$app->run('/dashboard/chat');

check('phases, first matching context and fallback run in order with hooks', $log === [
    'hook:beforeBoot', 'boot', 'register', 'hook:afterRegister', 'dashboard', 'hook:enterDashboard', 'fallback',
]);
check('current context is reported', $app->current() === 'dashboard' && $app->is('dashboard') && !$app->is('web'));
check('timeline records every executed step', array_column($app->timeline(), 'name') === ['boot', 'register', 'dashboard', 'fallback']);
check('second run is rejected', throws(fn () => $app->run('/')));
check('declaring after start is rejected', throws(fn () => $app->phase('late', fn () => null)));
check('duplicate phase is rejected', throws(fn () => new Manager()->phase('boot', fn () => null)->phase('boot', fn () => null)));

$log = [];
Hook::add('beforeSkipped', function () use (&$log) { $log[] = 'hook:beforeSkipped'; });

$app = new Manager();
$app->phase('skipped', function () use (&$log) { $log[] = 'skipped'; }, fn () => false)
    ->phase('kept', function () use (&$log) { $log[] = 'kept'; }, fn () => true);
$app->run('/');
check('phase with false $when is skipped without hooks and timeline entry', $log === ['kept'] && array_column($app->timeline(), 'name') === ['kept']);

$seen = [];
$app  = new Manager();
$app->phase('extensions', function () use (&$app, &$seen) { $seen[] = $app->is('dashboard'); })
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
$app->phase('boot', fn () => throw new RuntimeException('boom'));
try {
    $app->run('/');
} catch (RuntimeException) {
}
check('failed step is still recorded in timeline', array_column($app->timeline(), 'name') === ['boot']);

$caught = null;
$app    = new Manager();
$app->phase('boot', fn () => throw new RuntimeException('boom'))
    ->phase('register', function () use (&$log) { $log[] = 'register after error'; })
    ->catch(function (Throwable $e) use (&$caught) { $caught = $e->getMessage(); });
$app->run('/');
check('catch receives the error and stops the remaining steps', $caught === 'boom' && !in_array('register after error', $log, true));
check('declaring catch after start is rejected', throws(fn () => $app->catch(fn () => null)));

// terminate runs at shutdown, so it is checked in a child process that exits from inside a phase
$child = <<<'PHP'
    <?php
    const EX_PATH = %s;
    require_once EX_PATH . 'autoload.php';
    Expansa\Facades\Hook::add('terminate', function () { echo 'terminate'; });
    $app = new Expansa\Lifecycle\Manager();
    $app->phase('boot', function () { echo 'boot,'; exit; });
    $app->run('/');
    PHP;
$file = tempnam(sys_get_temp_dir(), 'lifecycle');
file_put_contents($file, sprintf($child, var_export(EX_PATH, true)));
$output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file));
unlink($file);
check('terminate hook runs after the response, even after exit', $output === 'boot,terminate');

exit($failures > 0 ? 1 : 0);
