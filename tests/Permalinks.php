<?php

declare(strict_types=1);

use App\Support\Permalinks;
use Expansa\Http\Exceptions\ValidationFailed;

// run: php tests/Permalinks.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

check('empty structures take the defaults', Permalinks::normalize([]) === Permalinks::DEFAULTS);
check('slashes at the ends and doubled ones are dropped, the case is lowered', Permalinks::normalize(['single' => '/Blog//%type%/%slug%/', 'categories' => 'topics/%slug%'])['single'] === 'blog/%type%/%slug%');
check('a structure without %slug% is refused', throws(fn () => Permalinks::normalize(['single' => 'post/%id%']), ValidationFailed::class));
check('a tag of another structure is refused', throws(fn () => Permalinks::normalize(['categories' => '%type%/%slug%']), ValidationFailed::class));
check('an unknown tag is refused', throws(fn () => Permalinks::normalize(['single' => '%year%/%slug%']), ValidationFailed::class));
check('a lone percent sign is refused', throws(fn () => Permalinks::normalize(['single' => '100%/%slug%']), ValidationFailed::class));
check('other characters are refused', throws(fn () => Permalinks::normalize(['single' => 'блог/%slug%']), ValidationFailed::class));

exit($failures > 0 ? 1 : 0);
