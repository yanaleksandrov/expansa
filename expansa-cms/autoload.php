<?php

declare(strict_types=1);

// platform check
if (PHP_VERSION_ID < 50600) {
    if (! headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
    $err = 'Composer 2.3.0 dropped support for autoloading on PHP <5.6 and you are running ' . PHP_VERSION . ', please upgrade PHP or use Composer 2.2 LTS via "composer self-update --2.2". Aborting.' . PHP_EOL;
    if (! ini_get('display_errors')) {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            fwrite(STDERR, $err);
        } elseif (! headers_sent()) {
            echo $err;
        }
    }
    trigger_error($err, E_USER_ERROR);
}

// autoload class
spl_autoload_register(function ($class) {
    $filepath = sprintf('%s%s.php', EX_PATH, $class);

    // TODO: is so bad, fix it!
    $filepath = str_replace(
        ['\\', '/Expansa/', '/App/'],
        ['/', '/expansa/', '/app/'],
        $filepath
    );

    require_once $filepath;
});
