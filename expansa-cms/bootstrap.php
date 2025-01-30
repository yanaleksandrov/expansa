<?php

declare(strict_types=1);

use Expansa\Support\Is;
use Expansa\Facades\Debug;

const EX_PATH                   = __DIR__ . '/';
const EX_VERSION                = '2025.1';
const EX_REQUIRED_PHP_VERSION   = '8.3';
const EX_REQUIRED_MYSQL_VERSION = '8.0';
const EX_REQUIRED_MEMORY        = 128;

// include required autoload.
require_once EX_PATH . 'autoload.php';

// register base Expansa functions.
require_once EX_PATH . 'expansa/functions.php';

// basic constants for the environment
if (is_file(EX_PATH . 'env.php')) {
    require_once EX_PATH . 'env.php';
}

// start benchmark timer
metrics()->start();

// launch the installer if Expansa is not installed.
require_once EX_PATH . 'install.php';

// base PHP & MySQL versions checker
require_once EX_PATH . 'public/error.php';

Debug::start(EX_DEBUG, EX_DEBUG_VIEW, function () {
    // determine if the application is in maintenance mode...
    if (is_file($maintenance = EX_PATH . 'maintenance.php')) {
        require $maintenance;
    }

    // application default data
    require_once EX_PATH . 'resources/countries.php';
    require_once EX_PATH . 'resources/timezones.php';
    require_once EX_PATH . 'resources/languages.php';

    // register default Expansa data
    require_once EX_PATH . 'migrations.php';

    // the initial configuration of the application
    require_once EX_PATH . 'app.php';

    // register Expansa routes
    require_once EX_PATH . 'routes.php';
});
