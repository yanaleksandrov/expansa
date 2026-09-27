<?php

declare(strict_types=1);

use Expansa\Session\Exceptions\NotStarted;
use Expansa\Session\Manager;
use Expansa\Session\Providers\Memory;
use Expansa\Session\Providers\Native;

// run: php tests/Session.php
require_once __DIR__ . '/bootstrap.php';

$session = new Memory('test');

check('not started before start()', ! $session->started && $session->id === '');

$session->start();
check('start() sets the id', $session->started && str_starts_with($session->id, 'sess_'));
check('name comes from the constructor', $session->name === 'test');

$session->set('user', 42);
$session->setValues(['a' => 1, 'b' => null]);
check('get() and has() see set values', $session->get('user') === 42 && $session->has('b') && $session->get('b', 'x') === 'x');

$session->forget('a');
check('forget() removes one value', ! $session->has('a') && $session->has('user'));

$session->flash->add('notice', 'Saved');
$session->flash->add('notice', 'Again');
check('flash messages are stored in the session data', $session->all()['_flash']['notice'] === ['Saved', 'Again']);
check('pull() returns the messages once', $session->flash->pull('notice') === ['Saved', 'Again'] && ! $session->flash->has('notice'));

$session->flash->set('error', ['Failed']);
check('pullAll() returns all messages and forgets them', $session->flash->pullAll() === ['error' => ['Failed']] && $session->flash->pullAll() === []);

$id = $session->id;
$session->flash->add('notice', 'Kept');
$session->delete();
check('delete() forgets values and flash messages', $session->all() === [] && ! $session->flash->has('notice'));
check('delete() regenerates the id', $session->id !== $id);

$native = new Native(['name' => 'expansa']);
$native->set('key', 'value');
check('native session keeps values in memory before start()', ! $native->started && $native->get('key') === 'value');
check('regenerateId() before start() throws NotStarted', throws(fn () => $native->regenerateId(), NotStarted::class));

// the manager: the configured driver, created on first use
$manager = new Manager();
check('an unconfigured manager uses the native session', $manager->driverName === 'native' && $manager->driver() instanceof Native);

$manager->configure(driver: 'memory', options: ['name' => 'console']);
check('configure() picks the driver and drops the created one', $manager->driver() instanceof Memory && $manager->name === 'console');
check('a driver is created once', $manager->driver() === $manager->driver());

$manager->start();
$manager->set('user', 7);
$manager->getFlash()->add('notice', 'Saved');
check('session methods go to the driver', $manager->isStarted() && $manager->started && $manager->get('user') === 7
    && $manager->driver()->get('user') === 7 && $manager->flash->pull('notice') === ['Saved']);

$manager->extend('custom', fn (array $options) => new Memory($options['name']));
$manager->configure(driver: 'custom', options: ['name' => 'own']);
check('a custom driver gets the options', $manager->name === 'own');

$manager->configure(driver: 'none');
check('an unknown driver throws', throws(fn () => $manager->driver(), InvalidArgumentException::class));

exit($failures > 0 ? 1 : 0);
