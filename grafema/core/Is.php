<?php
namespace Grafema;

/**
 * This class provides a set of static methods to check various conditions, such as validating
 * email addresses and URLs, determining the type of server, and identifying the request type.
 *
 * @since 2025.1
 */
final class Is
{
	/**
	 * Checks whether the given string is a valid email address.
	 *
	 * This method uses PHP's built-in filter to validate the format of the email address.
	 *
	 * @param string $thing The email address to validate.
	 * @return bool True if the email is valid, false otherwise.
	 * @since  2025.1
	 */
	public static function email( string $thing ): bool
	{
		return filter_var( $thing, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Checks whether the given string is a valid URL.
	 *
	 * This method uses PHP's built-in filter to validate the format of the URL.
	 *
	 * @param string $thing The URL to validate.
	 * @return bool True if the URL is valid, false otherwise.
	 * @since  2025.1
	 */
	public static function url( string $thing ): bool
	{
		return filter_var( $thing, FILTER_VALIDATE_URL );
	}

	/**
	 * Tests if the current browser runs on a mobile device (smartphone, tablet, etc.).
	 *
	 * This method checks the user agent string against known mobile device patterns.
	 *
	 * @see    http://detectmobilebrowsers.com
	 * @return bool True if the current browser is a mobile device, false otherwise.
	 * @since  2025.1
	 */
	public static function mobile(): bool
	{
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		if (
			preg_match(
				'/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',
				$useragent
			)
			|| preg_match(
				'/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',
				substr( $useragent, 0, 4 )
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether the server software is Apache or LiteSpeed.
	 *
	 * @return bool True if the server is running Apache or LiteSpeed, false otherwise.
	 * @since  2025.1
	 */
	public static function apache(): bool
	{
		return str_contains( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) || str_contains( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' );
	}

	/**
	 * Checks whether the server software is IIS.
	 *
	 * @return bool True if the server is running IIS, false otherwise.
	 * @since  2025.1
	 */
	public static function iis(): bool
	{
		return ! self::apache() && ( str_contains( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) || str_contains( $_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer' ) );
	}

	/**
	 * Checks whether the server software is Nginx.
	 *
	 * @return bool True if the server is running Nginx, false otherwise.
	 * @since  2025.1
	 */
	public static function nginx(): bool
	{
		return str_contains( $_SERVER['SERVER_SOFTWARE'], 'nginx' );
	}

	/**
	 * Determines if SSL is used.
	 *
	 * This method checks the HTTPS environment variable and server port to determine
	 * if the request is made over SSL.
	 *
	 * @return bool True if SSL is being used, false otherwise.
	 * @since  2025.1
	 */
	public static function ssl(): bool
	{
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( strtolower( $_SERVER['HTTPS'] ) === 'on' ) {
				return true;
			}

			if ( $_SERVER['HTTPS'] === '1' ) {
				return true;
			}
		} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] === '443' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether the given variable is a Grafema Errors.
	 *
	 * Returns whether `$thing` is an instance of the `Errors` class.
	 *
	 * @param mixed $thing The variable to check
	 * @return bool        Whether the variable is an instance of Errors
	 * @since  2025.1
	 */
	public static function error( mixed $thing ): bool
	{
		return $thing instanceof Error;
	}

	/**
	 * Determines whether the current request is for an administrative interface page.
	 *
	 * Function is not intended to be used for security purposes. Returns `true` whenever the current URL points to the admin part.
	 * Does not check whether the user is authorized or whether the user has access to the requested page.
	 * Is designed just to check the location in the admin panel and is not intended to protect requests.
	 * For checking roles and capabilities, use `User` class.
	 *
	 * @since  2025.1
	 *
	 * @return bool true if inside Grafema administration interface, false otherwise
	 */
	public static function dashboard(): bool
	{
		return defined( 'GRFM_IS_DASHBOARD' ) && GRFM_IS_DASHBOARD === true;
	}

	/**
	 * Determines whether the current request is for install wizard page.
	 *
	 * @since  2025.1
	 */
	public static function install(): bool
	{
		return defined( 'GRFM_IS_INSTALL' ) && GRFM_IS_INSTALL === true;
	}

	/**
	 * Checks if the application is in debug mode.
	 *
	 * @return bool True if debug mode is enabled, false otherwise.
	 * @since  2025.1
	 */
	public static function debug(): bool {
		return defined( 'GRFM_DEBUG' ) && GRFM_DEBUG === true;
	}

	/**
	 * Checks whether the database is installed.
	 *
	 * @since  2025.1
	 *
	 * @return bool true if Grafema is installed
	 */
	public static function installed(): bool
	{
		if ( ! defined( 'GRFM_PATH' ) || ! file_exists( GRFM_PATH . 'env.php' ) ) {
			return false;
		}

		Db::init();

		$schema = Db::schema();
		if ( empty( $schema ) ) {
			return false;
		}

		return isset( $schema[ GRFM_DB_PREFIX . Option::$table ] ) && ! empty( Option::get( 'site.url' ) );
	}

	/**
	 * Checks if the current request is an AJAX query.
	 *
	 * @return bool True if the request is an AJAX query, false otherwise.
	 * @since  2025.1
	 */
	public static function ajax(): bool
	{
		return defined( 'GRFM_DOING_AJAX' ) && GRFM_DOING_AJAX === true;
	}

	/**
	 * Determines if the given string is in JSON format.
	 *
	 * @param mixed $thing The variable to check.
	 * @return bool        True if the string is in JSON format, false otherwise.
	 * @since  2025.1
	 */
	public static function json( mixed $thing ): bool
	{
		return is_string( $thing ) && is_array( json_decode( $thing, true ) );
	}

	/**
	 * Determines if the given string is a valid timestamp format.
	 *
	 * @param mixed $thing The variable to check.
	 * @return bool        True if the string is a valid timestamp format, false otherwise.
	 * @since  2025.1
	 */
	public static function timestamp( mixed $thing ): bool
	{
		if ( strlen( (int) $thing ) === 10 ) {
			try {
				new \DateTime( '@' . $thing );
			} catch ( \Exception $e ) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Check value to find if it was serialized.
	 *
	 * If $data is not string, then returned value will always be false.
	 * Serialized data is always a string.
	 *
	 * @since 2025.1
	 *
	 * @param string $data   value to check to see if was serialized
	 * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
	 *
	 * @return bool false if not serialized and true if it was
	 */
	public static function serialized( $data, bool $strict = true ): bool
	{
		// If it isn't a string, it isn't serialized.
		if ( ! is_string( $data ) ) {
			return false;
		}

		$data = trim( $data );
		if ( $data === 'N;' ) {
			return true;
		}
		if ( strlen( $data ) < 4 ) {
			return false;
		}
		if ( $data[1] !== ':' ) {
			return false;
		}
		if ( $strict ) {
			$lastc = substr( $data, -1 );
			if ( $lastc !== ';' && $lastc !== '}' ) {
				return false;
			}
		} else {
			$semicolon = strpos( $data, ';' );
			$brace     = strpos( $data, '}' );
			// Either ; or } must exist.
			if ( $semicolon === false && $brace === false ) {
				return false;
			}
			// But neither must be in the first X characters.
			if ( $semicolon !== false && $semicolon < 3 ) {
				return false;
			}
			if ( $brace !== false && $brace < 4 ) {
				return false;
			}
		}
		$token = $data[0];
		switch ( $token ) {
			case 's':
				if ( $strict ) {
					if ( substr( $data, -2, 1 ) !== '"' ) {
						return false;
					}
				} elseif ( strpos( $data, '"' ) === false ) {
					return false;
				}
				// Or else fall through.
				// no break
			case 'a':
			case 'O':
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';

				return (bool) preg_match( "/^{$token}:[0-9.E+-]+;{$end}/", $data );
		}

		return false;
	}
}
