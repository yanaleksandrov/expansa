<?php
/**
 * Requests for PHP, an HTTP library.
 *
 * Autoloader for Requests for PHP.
 *
 * Include this file if you'd like to avoid having to create your own autoloader.
 *
 * @copyright 2012-2023 Requests Contributors
 * @license   https://github.com/WordPress/Requests/blob/stable/LICENSE ISC
 * @link      https://github.com/WordPress/Requests
 */

namespace Expansa\Http;

/*
 * Ensure the autoloader is only declared once.
 * This safeguard is in place as this is the typical entry point for this library
 * and this file being required unconditionally could easily cause
 * fatal "Class already declared" errors.
 */
if (class_exists('Expansa\Http\Autoload') === false) {

	/**
	 * Autoloader for Requests for PHP.
	 *
	 * This autoloader supports the PSR-4 based Requests 2.0.0 classes in a case-sensitive manner
	 * as the most common server OS-es are case-sensitive and the file names are in mixed case.
	 *
	 * For the PSR-0 Requests 1.x BC-layer, requested classes will be treated case-insensitively.
	 *
	 * @package Requests
	 * @since   2.0.0
	 */
	final class Autoload {

		/**
		 * List of the old PSR-0 class names in lowercase as keys with their PSR-4 case-sensitive name as a value.
		 *
		 * @var array
		 */
		private static $deprecated_classes = [
			// Interfaces.
			'requests_auth'                              => '\Expansa\Http\Contracts\Auth',
			'requests_hooker'                            => '\Expansa\Http\Contracts\HookManager',
			'requests_proxy'                             => '\Expansa\Http\Contracts\Proxy',
			'requests_transport'                         => '\Expansa\Http\Contracts\Transport',

			// Classes.
			'requests_cookie'                            => '\Expansa\Http\Cookie',
			'requests_exception'                         => '\Expansa\Http\Exception',
			'requests_hooks'                             => '\Expansa\Http\Hooks',
			'requests_idnaencoder'                       => '\Expansa\Http\IdnaEncoder',
			'requests_ipv6'                              => '\Expansa\Http\Ipv6',
			'requests_iri'                               => '\Expansa\Http\Iri',
			'requests_response'                          => '\Expansa\Http\Response',
			'requests_session'                           => '\Expansa\Http\Session',
			'requests_ssl'                               => '\Expansa\Http\Ssl',
			'requests_auth_basic'                        => '\Expansa\Http\Auth\Basic',
			'requests_cookie_jar'                        => '\Expansa\Http\Cookie\Jar',
			'requests_proxy_http'                        => '\Expansa\Http\Proxy\Http',
			'requests_response_headers'                  => '\Expansa\Http\Response\Headers',
			'requests_transport_curl'                    => '\Expansa\Http\Transport\Curl',
			'requests_transport_fsockopen'               => '\Expansa\Http\Transport\Fsockopen',
			'requests_utility_caseinsensitivedictionary' => '\Expansa\Http\Utility\CaseInsensitiveDictionary',
			'requests_utility_filterediterator'          => '\Expansa\Http\Utility\FilteredIterator',
			'requests_exception_http'                    => '\Expansa\Http\Exception\Http',
			'requests_exception_transport'               => '\Expansa\Http\Exception\Transport',
			'requests_exception_transport_curl'          => '\Expansa\Http\Exception\Transport\Curl',
			'requests_exception_http_304'                => '\Expansa\Http\Exception\Http\Status304',
			'requests_exception_http_305'                => '\Expansa\Http\Exception\Http\Status305',
			'requests_exception_http_306'                => '\Expansa\Http\Exception\Http\Status306',
			'requests_exception_http_400'                => '\Expansa\Http\Exception\Http\Status400',
			'requests_exception_http_401'                => '\Expansa\Http\Exception\Http\Status401',
			'requests_exception_http_402'                => '\Expansa\Http\Exception\Http\Status402',
			'requests_exception_http_403'                => '\Expansa\Http\Exception\Http\Status403',
			'requests_exception_http_404'                => '\Expansa\Http\Exception\Http\Status404',
			'requests_exception_http_405'                => '\Expansa\Http\Exception\Http\Status405',
			'requests_exception_http_406'                => '\Expansa\Http\Exception\Http\Status406',
			'requests_exception_http_407'                => '\Expansa\Http\Exception\Http\Status407',
			'requests_exception_http_408'                => '\Expansa\Http\Exception\Http\Status408',
			'requests_exception_http_409'                => '\Expansa\Http\Exception\Http\Status409',
			'requests_exception_http_410'                => '\Expansa\Http\Exception\Http\Status410',
			'requests_exception_http_411'                => '\Expansa\Http\Exception\Http\Status411',
			'requests_exception_http_412'                => '\Expansa\Http\Exception\Http\Status412',
			'requests_exception_http_413'                => '\Expansa\Http\Exception\Http\Status413',
			'requests_exception_http_414'                => '\Expansa\Http\Exception\Http\Status414',
			'requests_exception_http_415'                => '\Expansa\Http\Exception\Http\Status415',
			'requests_exception_http_416'                => '\Expansa\Http\Exception\Http\Status416',
			'requests_exception_http_417'                => '\Expansa\Http\Exception\Http\Status417',
			'requests_exception_http_418'                => '\Expansa\Http\Exception\Http\Status418',
			'requests_exception_http_428'                => '\Expansa\Http\Exception\Http\Status428',
			'requests_exception_http_429'                => '\Expansa\Http\Exception\Http\Status429',
			'requests_exception_http_431'                => '\Expansa\Http\Exception\Http\Status431',
			'requests_exception_http_500'                => '\Expansa\Http\Exception\Http\Status500',
			'requests_exception_http_501'                => '\Expansa\Http\Exception\Http\Status501',
			'requests_exception_http_502'                => '\Expansa\Http\Exception\Http\Status502',
			'requests_exception_http_503'                => '\Expansa\Http\Exception\Http\Status503',
			'requests_exception_http_504'                => '\Expansa\Http\Exception\Http\Status504',
			'requests_exception_http_505'                => '\Expansa\Http\Exception\Http\Status505',
			'requests_exception_http_511'                => '\Expansa\Http\Exception\Http\Status511',
			'requests_exception_http_unknown'            => '\Expansa\Http\Exception\Http\StatusUnknown',
		];

		/**
		 * Register the autoloader.
		 *
		 * Note: the autoloader is *prepended* in the autoload queue.
		 * This is done to ensure that the Requests 2.0 autoloader takes precedence
		 * over a potentially (dependency-registered) Requests 1.x autoloader.
		 *
		 * @internal This method contains a safeguard against the autoloader being
		 * registered multiple times. This safeguard uses a global constant to
		 * (hopefully/in most cases) still function correctly, even if the
		 * class would be renamed.
		 *
		 * @return void
		 */
		public static function register() {
			if (defined('REQUESTS_AUTOLOAD_REGISTERED') === false) {
				spl_autoload_register([self::class, 'load'], true);
				define('REQUESTS_AUTOLOAD_REGISTERED', true);
			}
		}

		/**
		 * Autoloader.
		 *
		 * @param string $class_name Name of the class name to load.
		 *
		 * @return bool Whether a class was loaded or not.
		 */
		public static function load($class_name) {
			// Check that the class starts with "Requests" (PSR-0) or "Expansa\Http" (PSR-4).
			$psr_4_prefix_pos = strpos($class_name, 'WpOrg\\Requests\\');

			if (stripos($class_name, 'Requests') !== 0 && $psr_4_prefix_pos !== 0) {
				return false;
			}

			$class_lower = strtolower($class_name);

			if ($class_lower === 'requests') {
				// Reference to the original PSR-0 Requests class.
				$file = dirname(__DIR__) . '/library/Requests.php';
			} elseif ($psr_4_prefix_pos === 0) {
				// PSR-4 classname.
				$file = __DIR__ . '/' . strtr(substr($class_name, 15), '\\', '/') . '.php';
			}

			if (isset($file) && file_exists($file)) {
				include $file;
				return true;
			}

			/*
			 * Okay, so the class starts with "Requests", but we couldn't find the file.
			 * If this is one of the deprecated/renamed PSR-0 classes being requested,
			 * let's alias it to the new name and throw a deprecation notice.
			 */
			if (isset(self::$deprecated_classes[$class_lower])) {
				/*
				 * Integrators who cannot yet upgrade to the PSR-4 class names can silence deprecations
				 * by defining a `REQUESTS_SILENCE_PSR0_DEPRECATIONS` constant and setting it to `true`.
				 * The constant needs to be defined before the first deprecated class is requested
				 * via this autoloader.
				 */
				if (!defined('REQUESTS_SILENCE_PSR0_DEPRECATIONS') || REQUESTS_SILENCE_PSR0_DEPRECATIONS !== true) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					trigger_error(
						'The PSR-0 `Requests_...` class names in the Requests library are deprecated.'
						. ' Switch to the PSR-4 `Expansa\Http\...` class names at your earliest convenience.',
						E_USER_DEPRECATED
					);

					// Prevent the deprecation notice from being thrown twice.
					if (!defined('REQUESTS_SILENCE_PSR0_DEPRECATIONS')) {
						define('REQUESTS_SILENCE_PSR0_DEPRECATIONS', true);
					}
				}

				// Create an alias and let the autoloader recursively kick in to load the PSR-4 class.
				return class_alias(self::$deprecated_classes[$class_lower], $class_name, true);
			}

			return false;
		}

		/**
		 * Get the array of deprecated Requests 1.x classes mapped to their equivalent Requests 2.x implementation.
		 *
		 * @return array
		 */
		public static function get_deprecated_classes() {
			return self::$deprecated_classes;
		}
	}
}
