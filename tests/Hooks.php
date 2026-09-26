<?php

declare(strict_types=1);

use Expansa\Facades\Hook;
use Expansa\Hooks\Exceptions\HooksException;

// run: php tests/Hooks.php
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
 * Number of listeners registered for a hook.
 */
function listeners(string $name): int
{
    $hooks = (new ReflectionProperty(Expansa\Hooks\Manager::class, 'hooks'))->getValue();

    return count($hooks[$name] ?? []);
}

$dir = sys_get_temp_dir() . '/expansa-hooks-' . getmypid();
@mkdir($dir);
file_put_contents("$dir/Greeting.php", '<?php namespace TestListeners; class Greeting { public function testGreeting(string $v): string { return "$v!"; } }');
file_put_contents("$dir/Farewell.php", '<?php namespace TestListeners; class Farewell { public function testFarewell(string $v): string { return "bye $v"; } }');

Hook::configure(listeners: "$dir/Greeting.php");
check('a listener file registers its public methods', listeners('testGreeting') === 1 && Hook::call('testGreeting', 'hi') === 'hi!');

Hook::configure(listeners: [TestListeners\Greeting::class]);
Hook::configure(listeners: $dir);
check('the same class via a list and a directory is registered once', listeners('testGreeting') === 1);
check('a directory scan picks up the other listener files', listeners('testFarewell') === 1 && Hook::call('testFarewell', 'Ann') === 'bye Ann');

try {
    Hook::configure(listeners: ['TestListeners\Missing']);
    check('a missing listener class is reported', false);
} catch (HooksException $e) {
    check('a missing listener class is reported', str_contains($e->getMessage(), 'does not exist'));
}

array_map('unlink', glob("$dir/*.php"));
rmdir($dir);

exit($failures > 0 ? 1 : 0);
