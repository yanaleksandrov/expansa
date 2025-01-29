<?php

declare(strict_types=1);

use Expansa\Api;

if (!defined('EX_IS_INSTALL')) {
    define('EX_IS_INSTALL', true);
}

/**
 * Adding API endpoints to install the system.
 */
Api::configure('/api', sprintf('%sapp/Api', EX_PATH));

echo view('install');
