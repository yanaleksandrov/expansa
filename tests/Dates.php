<?php

declare(strict_types=1);

use App\Support\Dates;

// run: php tests/Dates.php
require_once __DIR__ . '/bootstrap.php';

check('the presets of the settings', array_map(Dates::toPattern(...), ['F j, Y', 'Y-m-d', 'd.m.Y', 'g:i a', 'H:i']) === ['MMMM d, y', 'y-MM-dd', 'dd.MM.y', 'h:mm a', 'HH:mm']);
check('an escaped letter is quoted text', Dates::toPattern('j \d\e F') === "d' de 'MMMM");
check('a quote is doubled', Dates::toPattern("d'm") === "dd''MM");
check('a letter without an ICU part is left out', Dates::toPattern('jS F') === 'd MMMM');

exit($failures > 0 ? 1 : 0);
