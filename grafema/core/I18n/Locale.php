<?php
namespace Grafema\I18n;

/**
 * The Locale class is responsible for retrieving and storing the user's locale
 * based on the HTTP `Accept-Language` header. It provides a method to fetch the
 * locale in a standardized format, replacing underscores with hyphens.
 *
 * @package Grafema
 * @since 2025.1
 */
class Locale {

	/**
	 * Stores the locale determined from the HTTP request.
	 *
	 * @var string
	 * @since 2025.1
	 */
    private static string $locale;

	/**
	 * Get local from HTTP.
	 *
	 * @param string $default
	 * @return string
	 *
	 * @since 2025.1
	 */
	protected static function getLocale( string $default = 'en-US' ): string {
        if ( ! isset( self::$locale ) && function_exists( 'locale_accept_from_http' ) ) {
            self::$locale = locale_accept_from_http( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? $default );
        }
        return str_replace( '_', '-', self::$locale ?? $default );
    }
}
