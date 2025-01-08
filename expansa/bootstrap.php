<?php

declare(strict_types=1);

/**
 * Setup system core constants.
 *
 * @since 2025.1
 */

use app\User;
use Expansa\{Db, Debug, I18n, Is,};

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

/**
 * Launch debug mode & run benchmark.
 *
 * @since 2025.1
 */
Debug::launch();
Debug::timer();

/**
 * Generate CSRF token.
 *
 * @since 2025.1
 */
try {
    (new Expansa\Security\Csrf\Csrf())->generate('token');
} catch (Random\RandomException $e) {
}

require_once EX_CORE . 'functions.php';
echo '<pre>';
//$pdo = new PDO('mysql:host=localhost;dbname=' . EX_DB_NAME, EX_DB_USERNAME, EX_DB_PASSWORD);
//$connection = new \Expansa\Database\Drivers\MySQL\Connection($pdo, [
//    'database' => EX_DB_NAME,
//    'username' => EX_DB_USERNAME,
//    'password' => EX_DB_PASSWORD,
//    'host'     => EX_DB_HOST,
//    'prefix'   => EX_DB_PREFIX,
//    'driver'   => EX_DB_DRIVER,
//    'charset'  => EX_DB_CHARSET,
//]);
//print_r($pdo);
//print_r($connection);
//$database   = new \Expansa\Database\Drivers\MySQL\SchemaBuilder($connection);
Expansa\Schema::create('test_my_table', function (Expansa\Database\Schema\Table $table) {
    var_dump(43243454);
    $table->id();
    $table->timestamps();
});
echo '</pre>';

/**
 * Launch the installer if Expansa is not installed.
 *
 * @since 2025.1
 */
if (! Is::installed()) {
    Dashboard\Install::init();
    exit;
}

Expansa\Log::error('Test text', ['id' => 123]);

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

    $php_version               = strval(phpversion());
    $php_version_is_compatible = version_compare(EX_REQUIRED_PHP_VERSION, $php_version, '<=');
    if (! $php_version_is_compatible) {
        header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
        header('Content-Type: text/html; charset=utf-8');

        I18n::t(
            'Your server is running PHP version ":phpVersion" but Expansa :expansaVersion requires at least :phpRequiredVersion.',
            $php_version,
            EX_VERSION,
            EX_REQUIRED_PHP_VERSION
        );

        exit;
    }

    $db_version               = strval(Db::version());
    $db_version_is_compatible = version_compare(EX_REQUIRED_MYSQL_VERSION, $db_version, '<=');
    if (! $db_version_is_compatible) {
        header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
        header('Content-Type: text/html; charset=utf-8');

        I18n::t(
            'Your server is running PHP version ":dbVersion" but Expansa :expansaVersion requires at least :dbRequiredVersion.',
            $db_version,
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

require_once EX_PATH . 'migrations.php';
require_once EX_PATH . 'routes.php';

//
//echo '<pre>';
//Expansa\Hook::configure(EX_CORE . 'Listeners');
//
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
