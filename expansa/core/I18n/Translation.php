<?php
namespace Expansa\I18n;

/**
 *
 *
 * @package Expansa
 * @since 2025.1
 */
trait Translation {

	/**
	 * Incoming translations routes list.
	 *
	 * @var array
	 * @since 2025.1
	 */
	protected static array $routes = [];

	/**
	 *
	 *
	 * @var string
	 * @since 2025.1
	 */
	protected static string $pattern = '';

	/**
	 * Translates a given string based on the current locale. The method checks for
	 * a corresponding translation in a locale-specific JSON file. If a translation
	 * exists, it returns the translated string; otherwise, it returns the original string.
	 *
	 * The translation files are expected to be named according to the locale and
	 * follow a JSON format, where keys are original strings and values are their
	 * translations.
	 *
	 * @param string $string The string to be translated.
	 * @return string        The translated string, or the original if no translation is found.
	 *
	 * @since 2025.1
	 */
	protected static function get( string $string ): string {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
		$source    = $backtrace[1]['file'] ?? null;

		// add routes storages
		static $override = [];
		static $routes   = [];

		if ( $source !== null ) {
			if ( isset( $routes[ $source ] ) || isset( $override[ $source ] ) ) {
				return self::gettext( $string, $routes[ $source ] ?? '', $override[ $source ] ?? '' );
			}

			// check that incoming path is part of exist routes
			$segments = array_map( fn( $key ) => basename( rtrim( $key, '/' ) ), array_keys( self::$routes ) );
			$result   = implode( '|', $segments );
			$pattern  = sprintf( '/(%s)\/([^\/]+)\/[^\/]+$/', $result );
			if ( preg_match( $pattern, $source, $matches ) ) {
				$element   = $matches[1] ?? '';
				$directory = $matches[2] ?? '';
				$filename  = sprintf( self::$pattern, self::getLocale() );

				// try to find main & custom translations
				foreach ( self::$routes as $route => $targetRoute ) {
					if ( ! str_starts_with( $source, $route ) ) {
						continue;
					}

					$targetRoute = rtrim( $targetRoute, DIRECTORY_SEPARATOR );
					$targetDir   = basename( $targetRoute );
					if ( $directory ) {
						$targetRoute = str_replace( ':dirname', $directory, $targetRoute );
					}

					if ( in_array( $element, [ 'plugins', 'themes' ], true ) ) {
						$targetDir = $element . DIRECTORY_SEPARATOR . str_replace( ':dirname', $directory, $targetDir );
					}

					$override[ $source ] ??= sprintf( '%s%s/%s.json', EX_I18N, $targetDir, self::getLocale() );
					$routes[ $source ]   ??= sprintf( '%s/%s.json', $targetRoute, $filename );
				}

				return self::gettext( $string, $routes[ $source ] ?? '', $override[ $source ] ?? '' );
			}
		}
		return $string;
	}

	/**
	 * Find text from file.
	 *
	 * @param string $string
	 * @param string $filepath
	 * @param string $overrideFilepath
	 * @return string
	 */
	private static function gettext( string $string, string $filepath, string $overrideFilepath = '' ): string {
		foreach ( [ $overrideFilepath, $filepath ] as $path ) {
			if ( file_exists( $path ) ) {
				$translations = json_decode( file_get_contents( $path ) ?: '', true );
				if ( isset( $translations[ $string ] ) ) {
					return $translations[ $string ];
				}
			}
		}
		return $string;
	}
}
