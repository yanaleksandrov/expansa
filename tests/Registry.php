<?php

declare(strict_types=1);

use Expansa\Patterns\Registry;

// run: php tests/Registry.php
require_once __DIR__ . '/bootstrap.php';

$calls = 0;
Registry::lazy('lazy', function () use (&$calls) {
    $calls++;

    return ['a' => ['b' => 1]];
});

check('resolver does not run on declaration', $calls === 0);
check('dotted get resolves the top-level key', Registry::get('lazy.a.b') === 1);
check('resolver runs once', Registry::get('lazy') === ['a' => ['b' => 1]] && $calls === 1);

Registry::lazy('overridden', fn () => 'lazy');
Registry::set('overridden', 'set');
check('set() before first get() wins over the resolver', Registry::get('overridden') === 'set');

Registry::set('eager', 'value');
Registry::lazy('eager', fn () => 'lazy');
check('lazy() does not replace an existing value', Registry::get('eager') === 'value');

check('missing key returns default', Registry::get('missing', 'default') === 'default');

exit($failures > 0 ? 1 : 0);
