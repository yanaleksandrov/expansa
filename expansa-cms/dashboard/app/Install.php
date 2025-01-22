<?php

declare(strict_types=1);

namespace Dashboard;

use Expansa\Api;
use Expansa\Html;
use Expansa\Patterns\Singleton;
use Expansa\Route;
use Expansa\Url;
use Expansa\View;

/**
 *
 *
 * @package Expansa
 */
final class Install
{
    use Singleton;

    /**
     * Class constructor
     *
     * @return void
     * @throws \Exception
     */
    public function __construct()
    {
        if (!defined('EX_IS_INSTALL')) {
            define('EX_IS_INSTALL', true);
        }

        /**
         * Add core API endpoints.
         * Important! If current request is request to API, stop code execution after Api::create().
         *
         * @since 2025.1
         */
        Api::configure('/api', sprintf('%sdashboard/app/Api', EX_PATH));


        /**
         * Register new routes
         *
         * @since 2025.1
         */
        Route::get('(.*)', function ($slug) {
            http_response_code(200);

            /**
             * Redirect to installer wizard if Expansa is not installed.
             *
             * @since 2025.1
             */
            if ($slug !== 'install') {
                View::redirect(Url::site('install'));
                exit;
            }

            /**
             * The administrative panel also has a single entry point.
             *
             * @since 2025.1
             */
            echo View::make('install');
            //\app\View::print('views/install.blade');
        });

        Route::run(fn() => die());
    }
}
