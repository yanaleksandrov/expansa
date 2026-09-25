<?php

declare(strict_types=1);

if (extension_loaded('xhprof')) {
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

require_once __DIR__ . '/bootstrap.php';
