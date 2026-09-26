<?php

declare(strict_types=1);

use Expansa\Patterns\Registry;

// run: php tests/Registry.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

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

foreach (['countries' => 250, 'timezones' => 262, 'languages' => 119] as $key => $count) {
    Registry::lazy($key, fn () => require EX_PATH . "dashboard/data/$key.php");
    check("dashboard/data/$key.php resolves to $count entries", count(Registry::get($key)) === $count);
}

exit($failures > 0 ? 1 : 0);
