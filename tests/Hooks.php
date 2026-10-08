<?php

declare(strict_types=1);

use Expansa\Facades\Hook;
use Expansa\Hooks\Exceptions\InvalidListener;

// run: php tests/Hooks.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

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
} catch (InvalidListener $e) {
    check('a missing listener class is reported', str_contains($e->getMessage(), 'does not exist'));
}

// actions: every listener gets the same arguments, what they return is ignored
$seen = [];
Hook::add('testAction', function (string $a, int $b) use (&$seen) {
    $seen[] = "$a$b";
});
Hook::add('testAction', function (string $a, int $b) use (&$seen) {
    $seen[] = "$a$b";

    return 'ignored';
});
Hook::run('testAction', 'x', 1);
check('run() gives every listener the same arguments', $seen === ['x1', 'x1']);
check('run() is counted by calls()', Hook::calls('testAction') === 1);
Hook::run('testNobodyListens', 'x');
check('run() of a hook without listeners is counted too', Hook::calls('testNobodyListens') === 1);

Hook::add('testRecursion', function () {
    Hook::run('testRecursion');
});
check('run() stops a listener re-triggering its own hook', throws(fn () => Hook::run('testRecursion'), LogicException::class));
Hook::add('testAfterRecursion', fn () => null);
check('the hook runs again after the recursion error', throws(fn () => Hook::run('testAfterRecursion')) === false);

// filters: a listener that returns nothing loses the value, which is reported
$warnings = [];
set_error_handler(function (int $level, string $message) use (&$warnings): bool {
    $warnings[] = $message;

    return true;
}, E_USER_WARNING);

Hook::add('testFilter', fn (string $value) => "$value!");
check('a filter returns the value of its listeners', Hook::call('testFilter', 'hi') === 'hi!' && $warnings === []);

Hook::add('testLostFilter', function (string $value) {
});
Hook::add('testLostFilter', fn (?string $value) => $value ?? 'default');
$result = Hook::call('testLostFilter', 'hi');
check('a filter listener returning null is reported with its place', count($warnings) === 1 && str_contains($warnings[0], "'testLostFilter'") && str_contains($warnings[0], 'Hooks.php:'));
check('the next listener gets the null', $result === 'default');

$warnings = [];
Hook::add('testNullFilter', fn ($value) => null);
Hook::call('testNullFilter');
check('a null passed to a filter is not reported', $warnings === []);
restore_error_handler();

array_map('unlink', glob("$dir/*.php"));
rmdir($dir);

exit($failures > 0 ? 1 : 0);
