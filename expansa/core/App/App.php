<?php

namespace Expansa\App;

use Expansa\Db;
use Expansa\Db\Medoo;

/**
 * The main class for working with the application system.
 *
 * @since 2025.1
 */
class App {

	/**
	 * Defines a named constant.
	 *
	 * @param string $name
	 * @param $value
	 * @since 2025.1
	 */
	public function define( string $name, $value ): void {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Check application system
	 *
	 * @param string $parameter
	 * @param string|int $value
	 * @return bool
	 */
	public static function check( string $parameter, string|int $value ): bool {
		return match ( $parameter ) {
			'php'        => version_compare( $value, strval( phpversion() ), '<=' ),
			'mysql'      => version_compare( $value, strval( Db::version() ), '<=' ),
			'connection' => Db::check() instanceof Medoo,
			'memory'     => intval( ini_get( 'memory_limit' ) ) >= intval( $value ),
			default      => false,
		};
	}

	/**
	 * Check application extensions
	 *
	 * @param string $extension
	 * @return bool
	 */
	public static function has( string $extension ): bool {
		return extension_loaded( $extension );
	}
}
