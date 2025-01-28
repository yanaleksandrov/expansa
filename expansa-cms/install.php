<?php

declare(strict_types=1);

use Expansa\Api;
use Expansa\Route;

/**
 * Installation.
 *
 * @since 2025.1
 */
Route::get('/install', function () {
    if (!defined('EX_IS_INSTALL')) {
        define('EX_IS_INSTALL', true);
    }

    /**
     * Adding API endpoints to install the system.
     */
    Api::configure('/api', sprintf('%sapp/Api', EX_PATH));

    echo view('install');
});

/**
 * Launch routing.
 *
 * @since 2025.1
 */
Route::run(fn() => die());
