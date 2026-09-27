<?php

declare(strict_types=1);

use Expansa\Cache\Manager;
use Expansa\Cache\Providers\File;
use Expansa\Cache\Providers\Memory;

// run: php tests/Cache.php
require_once __DIR__ . '/bootstrap.php';

$directory = sys_get_temp_dir() . '/expansa-cache-test-' . getmypid();

// every provider without a server behaves the same
foreach (['Memory' => new Memory(), 'File' => new File($directory)] as $name => $cache) {
    check("$name: add() stores a missing key", $cache->add('a', 1, 'test') === true && $cache->get('a', 'test') === 1);
    check("$name: add() keeps an existing key", $cache->add('a', 2, 'test') === false && $cache->get('a', 'test') === 1);
    check("$name: add() skips a passed expiry", $cache->add('old', 1, 'test', '-1 minute') === false && $cache->get('old', 'test') === null);
    check("$name: set() overwrites", $cache->set('a', 3, 'test') === true && $cache->get('a', 'test') === 3);

    check("$name: get() returns and stores the callback result on a miss", $cache->get('lazy', 'test', fn () => 'value') === 'value'
        && $cache->get('lazy', 'test', fn () => 'other') === 'value');
    check("$name: a cached null is a hit", $cache->get('null', 'test', fn () => null) === null
        && $cache->get('null', 'test', fn () => 'other') === null);

    check("$name: increase() and decrease()", $cache->increase('a', 2, 'test') && $cache->decrease('a', 1, 'test') && $cache->get('a', 'test') === 4);
    check("$name: increase() fails on a missing key", $cache->increase('missing', 1, 'test') === false);

    check("$name: pull() returns and forgets", $cache->pull('a', 'test') === 4 && $cache->get('a', 'test') === null);

    $cache->suspend(function () use ($cache, $name) {
        check("$name: a suspended key rejects add()", $cache->add('locked', 1, 'test') === false);
        check("$name: get() of a suspended key returns the callback result", $cache->get('locked', 'test', fn () => 5) === 5);
    }, 'locked', 'test');
    check("$name: the lock is released", $cache->get('locked', 'test') === null && $cache->add('locked', 1, 'test'));

    $cache->set('b', 1, 'other');
    $cache->forget('', 'test');
    check("$name: forget() of a group keeps other groups", $cache->get('lazy', 'test') === null && $cache->get('b', 'other') === 1);
}

// a new instance reads what the first one wrote to disk
new File($directory)->set('shared', 'disk', 'test');
check('File: values outlive the instance', new File($directory)->get('shared', 'test') === 'disk');
new File($directory)->forget('', 'test');
new File($directory)->forget('', 'other');
@rmdir($directory);

// the manager: stores from the configuration, created on first use
$manager = new Manager();
check('an unconfigured manager keeps values in memory', $manager->defaultStore === 'memory' && $manager->store() instanceof Memory);

$manager->configure([
    'files'  => ['driver' => 'file', 'path' => $directory],
    'memory' => ['driver' => 'memory'],
]);
check('the first store is the default one', $manager->defaultStore === 'files' && $manager->store() instanceof File);
check('provider methods use the default store', $manager->set('k', 'v', 'test') && $manager->store('files')->get('k', 'test') === 'v'
    && $manager->store('memory')->get('k', 'test') === null);
check('a store is created once', $manager->store('memory') === $manager->store('memory'));

$manager->configure(['memory' => ['driver' => 'memory'], 'files' => ['driver' => 'file', 'path' => $directory]], 'memory');
check('configure() picks the default store and drops created ones', $manager->defaultStore === 'memory' && $manager->stores === []);

$manager->extend('custom', fn (array $config) => new Memory());
$manager->configure(['own' => ['driver' => 'custom'], 'bad' => ['driver' => 'none'], 'nopath' => ['driver' => 'file']]);
check('a custom driver creates the store', $manager->store('own') instanceof Memory);
check('unknown store, driver and a missing option throw', throws(fn () => $manager->store('missing'), InvalidArgumentException::class)
    && throws(fn () => $manager->store('bad'), InvalidArgumentException::class)
    && throws(fn () => $manager->store('nopath'), InvalidArgumentException::class));
check('an unconfigured default store throws', throws(fn () => $manager->configure(['a' => ['driver' => 'memory']], 'b'), InvalidArgumentException::class));

new File($directory)->forget('', 'test');
@rmdir($directory);

exit($failures > 0 ? 1 : 0);
