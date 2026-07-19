<?php

declare(strict_types=1);

use Expansa\Facades\Debug;
use Expansa\Facades\Route;

const EX_PATH                   = __DIR__ . '/';
const EX_VERSION                = '2025.6';
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
require_once EX_PATH . 'dashboard/error.php';

Debug::start(EX_DEBUG, EX_DEBUG_VIEW, function () {
    // determine if the application is in maintenance mode...
    if (is_file($maintenance = EX_PATH . 'maintenance.php')) {
        require $maintenance;
    }

    // application default data
    require_once EX_PATH . 'dashboard/data/countries.php';
    require_once EX_PATH . 'dashboard/data/timezones.php';
    require_once EX_PATH . 'dashboard/data/languages.php';

    // the initial configuration of the application
    require_once EX_PATH . 'configs.php';

    // register default Expansa data
    require_once EX_PATH . 'migrations.php';

    // register Expansa routes
    require_once EX_PATH . 'routes/api.php';

    /**
     * Registers a base app route for handling all GET HTTP requests.
     *
     * @since 2025.1
     */
    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);

    /**
     * Launch routing & output page.
     *
     * @since 2025.1
     */
    Route::run();
});
