<?php

use Grafema\{
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
const GRFM_PATH                   = __DIR__ . '/';
const GRFM_VERSION                = '2025.1';
const GRFM_REQUIRED_PHP_VERSION   = '8.1';
const GRFM_REQUIRED_MYSQL_VERSION = '5.6';

/**
 * Include required files: app environment, autoload & configurations.
 *
 * @since 2025.1
 */
require_once GRFM_PATH . 'env.php';
require_once GRFM_PATH . 'autoload.php';
require_once GRFM_PATH . 'config.php';

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
 * Launch the installer if Grafema is not installed.
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
    $php_version_is_compatible = version_compare(GRFM_REQUIRED_PHP_VERSION, $php_version, '<=');
    if (! $php_version_is_compatible) {
        header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
        header('Content-Type: text/html; charset=utf-8');

	    I18n::f(
			'Your server is running PHP version ":phpVersion" but Grafema :grafemaVersion requires at least :phpRequiredVersion.',
			$php_version,
			GRFM_VERSION,
			GRFM_REQUIRED_PHP_VERSION
	    );

        exit;
    }

    $db_version               = strval(Db::version());
    $db_version_is_compatible = version_compare(GRFM_REQUIRED_MYSQL_VERSION, $db_version, '<=');
    if (! $db_version_is_compatible) {
        header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
        header('Content-Type: text/html; charset=utf-8');

	    I18n::f(
		    'Your server is running PHP version ":dbVersion" but Grafema :grafemaVersion requires at least :dbRequiredVersion.',
		    $db_version,
		    GRFM_VERSION,
		    GRFM_REQUIRED_MYSQL_VERSION
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

require_once GRFM_PATH . 'migrations.php';
require_once GRFM_PATH . 'routes.php';

//
//echo '<pre>';
//Grafema\Hook::configure(GRFM_CORE . 'Listeners');
//
//function grfmTest($hook) {
//	var_dump($hook);
//	var_dump('111111');
//	return $hook;
//}
//
//Grafema\Hook::add('testHook', 'grfmTest');
//
//
//Grafema\Hook::add('testHook', #[Grafema\Hooks\HookListenerAlias('heyheyhey')] function($hook) {
//	var_dump($hook);
//	var_dump('222222');
//	return $hook;
//}, Grafema\Hooks\Priority::HIGH);
//
//var_dump('--------------------------------------------------------------------------');
////Grafema\Hooks::flush('testHook', ['Grafema\Listeners\Test', 'testHook']);
////Grafema\Hooks::flush('testHook', 'grfmTest');
////Grafema\Hooks::flush('testHook', 'heyheyhey');
//
//print_r(Grafema\Hook::get());
//
//Grafema\Hook::call('testHook', 'my', 'name', 'is', 'Yan');
//echo '</pre>';

//echo '<pre>';
//$data    = 'Привет миру! Как у вас дела и что нового?';
//$data    = 'Hello world my name is Yan Aleksandrov';
//var_dump($data);
//$encoded = Grafema\Codec::encode(['test' => $data]);
//var_dump($encoded);
//$decoded = Grafema\Codec::decode($encoded);
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
//	$dictionary = new Grafema\Algorithms\Huffmans\Dictionary([(string)$text]);
//	$huffman    = new Grafema\Algorithms\Huffmans\Huffman($dictionary, '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
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