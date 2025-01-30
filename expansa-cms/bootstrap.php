<?php

declare(strict_types=1);

use Expansa\Is;
use Expansa\Debug;
use Expansa\Security\Csrf\Csrf;

const EX_PATH                   = __DIR__ . '/';
const EX_VERSION                = '2025.1';
const EX_REQUIRED_PHP_VERSION   = '8.3';
const EX_REQUIRED_MYSQL_VERSION = '8.0';
const EX_REQUIRED_MEMORY        = 128;

// Include required autoload.
require_once EX_PATH . 'autoload.php';

// Register base Expansa functions.
require_once EX_PATH . 'expansa/functions.php';

// Basic constants for the environment
if (is_file(EX_PATH . 'env.php')) {
    require_once EX_PATH . 'env.php';
}

// Start benchmark timer
metrics()->start();
//session()->start();

// Generate CSRF token.
(new Csrf())->generate('token');

// Launch the installer if Expansa is not installed.
if (! Is::installed()) {
    require_once EX_PATH . 'install.php';
    exit;
}

// Base PHP & MySQL versions checker.
require_once EX_PATH . 'dashboard/views/error.blade.php';

Debug::start(EX_DEBUG, EX_DEBUG_VIEW, function () {
    // Determine if the application is in maintenance mode...
    if (is_file($maintenance = EX_PATH . 'maintenance.php')) {
        require $maintenance;
    }

    // Application default data
    require_once EX_PATH . 'resources/countries.php';
    require_once EX_PATH . 'resources/timezones.php';
    require_once EX_PATH . 'resources/languages.php';

    // Register default Expansa data
    require_once EX_PATH . 'migrations.php';

    // The initial configuration of the application
    require_once EX_PATH . 'app.php';

    // Register Expansa routes
    require_once EX_PATH . 'routes.php';
});
