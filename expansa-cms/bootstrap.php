<?php

declare(strict_types=1);

use Expansa\Is;
use Expansa\Debug;
use Expansa\Security\Csrf\Csrf;

const EX_PATH                   = __DIR__ . '/';
const EX_VERSION                = '2025.1';
const EX_REQUIRED_PHP_VERSION   = '8.2';
const EX_REQUIRED_MYSQL_VERSION = '8.0';
const EX_REQUIRED_MEMORY        = 128;

// Include required autoload.
require_once EX_PATH . 'autoload.php';

// Register base Expansa functions.
require_once EX_PATH . 'expansa/functions.php';

// Generate CSRF token.
(new Csrf())->generate('token');

// Launch the installer if Expansa is not installed.
if (! Is::installed()) {
    Dashboard\Install::init();
    exit;
}

// Base PHP & MySQL versions checker.
require_once EX_PATH . 'dashboard/error.php';

/**
 *
 *
 * @since 2025.1
 */
Debug::start(EX_DEBUG_VIEW, function () {

    // Basic constants for the environment.
    require_once EX_PATH . 'env.php';

    // The initial configuration of the application.
    require_once EX_PATH . 'config.php';

    // Register default Expansa data.
    require_once EX_PATH . 'migrations.php';

    // Register Expansa routes.
    require_once EX_PATH . 'routes.php';
}, EX_DEBUG);
