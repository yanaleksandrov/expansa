<?php

use Expansa\{
    Db,
    Debug,
    I18n,
    Is,
    User,
    Csrf,
};

/**
 * Setup system core constants.
 *
 * @since 2025.1
 */
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
( new Csrf\Csrf(
    new Csrf\Providers\NativeHttpOnlyCookieProvider()
) )->generate('token');

/**
 * Launch the installer if Expansa is not installed.
 *
 * @since 2025.1
 */
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

    $php_version               = strval(phpversion());
    $php_version_is_compatible = version_compare(EX_REQUIRED_PHP_VERSION, $php_version, '<=');
    if (! $php_version_is_compatible) {
        header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
        header('Content-Type: text/html; charset=utf-8');

	    I18n::f(
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

	    I18n::f(
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
//	var_dump($hook);
//	var_dump('111111');
//	return $hook;
//}
//
//Expansa\Hook::add('testHook', 'expansaTest');
//
//
//Expansa\Hook::add('testHook', #[Expansa\Hooks\HookListenerAlias('heyheyhey')] function($hook) {
//	var_dump($hook);
//	var_dump('222222');
//	return $hook;
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
//$data    = 'Привет миру! Как у вас дела и что нового?';
//$data    = 'Hello world my name is Yan Aleksandrov';
//var_dump($data);
//$encoded = Expansa\Codec::encode(['test' => $data]);
//var_dump($encoded);
//$decoded = Expansa\Codec::decode($encoded);
//var_dump($decoded);
//echo '</pre>';


//echo '<pre>';
//$texts = [
//	'Its my life',
//	'{"widget":{"debug":"on","window":{"title":"Sample Konfabulator Widget","name":"main_window","width":500,"height":500},"image":{"src":"Images/Sun.png","name":"sun1","hOffset":250,"vOffset":250,"alignment":"center"},"text":{"data":"Click Here","size":36,"style":"bold","name":"text1","hOffset":250,"vOffset":100,"alignment":"center","onMouseUp":"sun1.opacity = (sun1.opacity / 100) * 90;"}}}',
//	'Привет, как у тебя дела?',
//	23124325234,
//	123,
//	'动物中国俄国',
//];
//
//foreach ($texts as $text) {
//	$dictionary = new Expansa\Algorithms\Huffmans\Dictionary([(string)$text]);
//	$huffman    = new Expansa\Algorithms\Huffmans\Huffman($dictionary, '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
//	$encoded = $huffman->encode($text, true);
//	$decoded = $huffman->decode($encoded, true);
//
//	printExampleResult($text, $encoded, $decoded);
//}
//
//function printExampleResult($str, $encodedStr, $decodedStr)
//{
//	$strLen = mb_strlen($str);
//	$encodedStrLen = mb_strlen($encodedStr);
//
//	echo    'Original string :	'.$str.'<br />'.
//		'Encoded string :	'.$encodedStr.'<br />'.
//		'Decoded string :	'.$decodedStr.'<br />'.
//		'Original length :	'.$strLen.'<br />'.
//		'Encoded length :	'.$encodedStrLen.'<br />'.
//		'Percentage gain :	'.(100 -(100 * $encodedStrLen / $strLen)).'%<br /><br />';
//}
//echo '</pre>';