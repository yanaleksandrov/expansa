<?php

declare(strict_types=1);

/**
 * Setup system core constants.
 *
 * @since 2025.1
 */

use app\User;
use Expansa\{Db, I18n, Is};
use Expansa\Debug;
use Expansa\Security\Csrf\Csrf;

const EX_PATH                   = __DIR__ . '/';
const EX_VERSION                = '2025.1';
const EX_REQUIRED_PHP_VERSION   = '8.1';
const EX_REQUIRED_MYSQL_VERSION = '5.6';

/**
 * Include required files: app environment, autoload & configurations.
 *
 * @since 2025.1
 */
require_once EX_PATH . 'env.php';
require_once EX_PATH . 'autoload.php';
require_once EX_PATH . 'config.php';

Debug::start(EX_DEBUG_VIEW, function () {

    // Generate CSRF token.
    (new Csrf())->generate('token');

    require_once EX_CORE . 'functions.php';

    // Launch the installer if Expansa is not installed.
    if (! Is::installed()) {
        Dashboard\Install::init();
        exit;
    }

    /**
     * Check for the required PHP version, and the MySQL extension or a database drop-in.
     * Dies if requirements are not met.
     *
     * @since  2025.1
     */
    (function () {
        $serverProtocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
        if (! in_array($serverProtocol, ['HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3'])) {
            $serverProtocol = 'HTTP/1.0';
        }

        $phpVersion             = strval(phpversion());
        $phpVersionIsCompatible = version_compare(EX_REQUIRED_PHP_VERSION, $phpVersion, '<=');
        if (! $phpVersionIsCompatible) {
            header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
            header('Content-Type: text/html; charset=utf-8');

            I18n::t(
                'Your server is running PHP version ":phpVersion" but Expansa :expansaVersion requires at least :phpRequiredVersion.',
                $phpVersion,
                EX_VERSION,
                EX_REQUIRED_PHP_VERSION
            );

            exit;
        }

        $dbVersion             = Db::version();
        $dbVersionIsCompatible = version_compare(EX_REQUIRED_MYSQL_VERSION, $dbVersion, '<=');
        if (! $dbVersionIsCompatible) {
            header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
            header('Content-Type: text/html; charset=utf-8');

            I18n::t(
                'Your server is running PHP version ":dbVersion" but Expansa :expansaVersion requires at least :dbRequiredVersion.',
                $dbVersion,
                EX_VERSION,
                EX_REQUIRED_MYSQL_VERSION
            );

            exit;
        }
    })();

    /**
     * Set up current user.
     *
     * @since 2025.1
     */
    User::current();

    Expansa\Hook::configure(EX_PATH . 'app/Listeners');

    require_once EX_PATH . 'migrations.php';
    require_once EX_PATH . 'routes.php';

    //echo '<pre>';
    //function expansaTest($hook) {
    //  var_dump($hook);
    //  var_dump('111111');
    //  return $hook;
    //}
    //
    //Expansa\Hook::add('testHook', 'expansaTest');
    //
    //
    //Expansa\Hook::add('testHook', #[Expansa\Hooks\HookListenerAlias('heyheyhey')] function($hook) {
    //  var_dump($hook);
    //  var_dump('222222');
    //  return $hook;
    //}, Expansa\Hooks\Priority::HIGH);
    //
    //var_dump('--------------------------------------------------------------------------');
    ////Expansa\Hooks::flush('testHook', ['Expansa\Listeners\Test', 'testHook']);
    ////Expansa\Hooks::flush('testHook', 'expansaTest');
    ////Expansa\Hooks::flush('testHook', 'heyheyhey');
    //
    //print_r(Expansa\Hook::get());
    //
    //Expansa\Hook::call('testHook', 'my', 'name', 'is', 'Yan');
    //echo '</pre>';

    //echo '<pre>';
    //print_r(Disk::dir(EX_PATH));
    //echo '</pre>';

    //echo '<pre>';
    //print_r(
    //    Expansa\Mail::to('test-zt9rpoi4o@srv1.mail-tester.com')
    //        ->from('yan@cms.codyshop.ru')
    //        ->message('Hello World!')
    //        ->send()
    //);
    //echo '</pre>';
}, EX_DEBUG);
