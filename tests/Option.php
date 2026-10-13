<?php

declare(strict_types=1);

use App\Models\Option;
use Expansa\Support\Arr;

// run: php tests/Option.php
require_once __DIR__ . '/bootstrap.php';

$name = new ReflectionMethod(Option::class, 'name');

check('a dotted name keeps its dots', $name->invoke(null, 'extensions.active') === 'extensions.active');
check('every part is sanitized', $name->invoke(null, 'Site.Na me!.x') === 'site.name.x');
check('empty parts are dropped', $name->invoke(null, '.a..b.') === 'a.b');
check('a plain name stays', $name->invoke(null, 'mail') === 'mail');

$nested = ['active' => ['plugins/a', 'plugins/b'], 'other' => 1];
Arr::set($nested, 'active', ['plugins/b']);
check('a nested list is replaced whole', $nested === ['active' => ['plugins/b'], 'other' => 1]);
