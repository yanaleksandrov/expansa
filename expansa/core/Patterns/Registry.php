<?php

namespace Expansa\Patterns;

use Expansa\Error;
use Expansa\I18n;

/**
 * The Registry pattern is designed primarily to solve the global scope problem.
 * This is a very common problem when you want to share data,
 * but it is not desirable to clog the global scope.
 *
 * 1. The example shows how to get access to string value by key:
 *
 * ```php
 * Registry::set( 'key', 'value' );
 * print_r( Registry::get( 'key' ) );
 * output: 'value'
 * ```
 *
 * 2. As a key you can specify point-separated values. In this case, the class will try to find the value in the array:
 *
 * ```php
 * Registry::set( 'attributes', [ 'textarea' => [ 'value', 'row' ] ] );
 * print_r( Registry::get( 'attributes.textarea' ) );
 * output: array( 'value', 'row' )
 * ```
 *
 * @since 2025.1
 */
final class Registry {

	/**
	 * Put item into the registry.
	 *
	 * @param string $key
	 * @param mixed $item
	 * @return void
	 */
	private static array $registry = [];

	/**
	 * Put item into the registry.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return void
	 */
	public static function set( string $key, mixed $value ): void {
		if ( ! isset( self::$registry[ $key ] ) ) {
			self::$registry[ $key ] = $value;
		} else {
			new Error( 'registry-set', I18n::_t( 'You trying to override an existing data.' ) );
		}
	}

	/**
	 * Get item by key.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return null|mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$array = self::$registry;

		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
				return $default;
			}
			$array = $array[ $segment ];
		}

		return $array;
	}
}
