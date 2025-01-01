<?php
namespace Grafema\Support;

/**
 * Utility class to handle operations on an array of objects or arrays.
 *
 * @since 2025.1
 */
class Arr {

	/**
	 * TODO: Memory-saving array iteration.
	 *
	 * @param array $array
	 * @return mixed
	 */
	public static function each( array $array ): mixed {

	}

	/**
	 * Recursively delete array elements with an empty values.
	 *
	 * @param array $array
	 * @return array
	 */
	public static function clean( array $array ): array {
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = self::clean( $value );
			}
		}
		return array_filter( $array );
	}

	/**
	 * Deletes all array elements that are in the blacklist.
	 *
	 * @param array $array
	 * @param array $black_list
	 * @return array
	 */
	public static function exclude( array $array, array $black_list ): array {
		return array_diff_key( $array, array_flip( $black_list ) );
	}

	/**
	 * Retrieves array elements that are included in the allowed list.
	 *
	 * @param array $array
	 * @param array $white_list
	 * @return array
	 */
	public static function extract( array $array, array $white_list ): array {
		return array_intersect_key( $array, array_flip( $white_list ) );
	}

	/**
	 * Adding new data to the array after the specified key. TODO: Support nested arrays with using "dot" notation.
	 *
	 * @param array      $array
	 * @param int|string $position
	 * @param mixed      $insert
	 * @return array
	 */
	public static function insert( array $array, int|string $position, mixed $insert ): array {
		$key_pos = array_search( $position, array_keys( $array ), true );
		if ( $key_pos !== false ) {
			$key_pos++;
			$second_array = array_splice( $array, $key_pos );
			$array        = array_merge( $array, $insert, $second_array );
		}
		return $array;
	}

	/**
	 * Sort array by pattern.
	 *
	 * @param array $arr
	 * @param array $pattern
	 * @return array
	 */
	public static function sortByPattern( array $arr, array $pattern ): array {
		$sorted = [];
		$array  = array_keys( $arr );
		foreach ( $pattern as $value ) {
			if ( in_array( $value, $array, true ) ) {
				$sorted[] = $value;
				$key      = array_search( $value, $array, true );
				unset( $arr[ $key ] );
			}
		}
		return array_merge( array_flip( $sorted ), $arr );
	}

	/**
	 * Sort multidimensional array by field. Value of property can be string or integer.
	 *
	 * @param array $array
	 * @param string $field
	 * @param int $order
	 * @return array
	 */
	public static function sort( array $array, string $field, int $order = SORT_ASC ): array {
		if ( empty( $array ) ) {
			return [];
		}

		array_multisort(
			array_column(
				$array,
				$field
			),
			$order,
			$array
		);

		return $array;
	}

	/**
	 * The most memory-efficient array_map_recursive().
	 *
	 * @param array    $array
	 * @param callable $callback
	 * @return array
	 */
	public static function map( array $array, callable $callback ): array {
		array_walk_recursive(
			$array,
			function( &$v ) use ( $callback ) {
				$v = $callback( $v );
			}
		);
		return $array;
	}

	/**
	 * Filters the list, based on a set of key => value arguments.
	 *
	 * Retrieves the objects from the list that match the given arguments.
	 * Key represents property name, and value represents property value.
	 *
	 * If an object has more properties than those specified in arguments,
	 * that will not disqualify it. When using the 'AND' operator,
	 * any missing properties will disqualify it.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $args     Optional. An array of key => value arguments to match
	 *                         against each object. Default empty array.
	 * @param string $operator Optional. The logical operation to perform. 'AND' means
	 *                         all elements from the array must match. 'OR' means only
	 *                         one element needs to match. 'NOT' means no elements may
	 *                         match. Default 'AND'.
	 * @return array           Array of found values.
	 */
	public static function filter( $array, $args, $operator = 'AND' ): array {
		$operator = strtoupper( $operator );
		if ( ! is_array( $array ) || empty( $args ) || ! in_array( $operator, [ 'AND', 'OR', 'NOT' ], true ) ) {
			return $array;
		}

		$count    = count( $args );
		$filtered = [];

		foreach ( $array as $key => $obj ) {
			$matched = 0;

			foreach ( $args as $m_key => $m_value ) {
				if ( is_array( $obj ) ) {
					// Treat object as an array.
					if ( array_key_exists( $m_key, $obj ) && ( $m_value === $obj[ $m_key ] ) ) {
						$matched++;
					}
				} elseif ( is_object( $obj ) ) {
					// Treat object as an object.
					if ( isset( $obj->{$m_key} ) && ( $m_value === $obj->{$m_key} ) ) {
						$matched++;
					}
				}
			}

			if (
				( 'AND' === $operator && $matched === $count )
					||
				( 'OR' === $operator && $matched > 0 )
					||
				( 'NOT' === $operator && 0 === $matched )
			) {
				$filtered[ $key ] = $obj;
			}
		}

		return $filtered;
	}

	/**
	 * Convert flat array to html attributes.
	 *
	 * @param array $attributes
	 * @return string
	 */
	public static function toHtmlAtts( array $attributes ): string {
		$boolean_attributes = [
			'accesskey',
			'async',
			'autofocus',
			'autoplay',
			'checked',
			'contenteditable',
			'controls',
			'disabled',
			'draggable',
			'hidden',
			'ismap',
			'loop',
			'multiple',
			'readonly',
			'required',
			'selected',

			'v-cloak',
			'x-cloak',
		];

		$atts = [];
		foreach ( $attributes as $attribute => $value ) {
			$attribute = trim( htmlspecialchars( $attribute, ENT_QUOTES ) );
			$value     = trim( htmlspecialchars( $value, ENT_QUOTES ) );
			if ( ! $attribute ) {
				continue;
			}

			if ( in_array( $attribute, $boolean_attributes, true ) ) {
				if ( $value ) {
					$atts[] = $attribute;
				}
			} else {
				$atts[] = match ( $attribute ) {
					'value' => sprintf( '%s="%s"', $attribute, $value ),
					default => $value ? sprintf( '%s="%s"', $attribute, $value ) : ( str_starts_with( $attribute, 'x-' ) ? $attribute : '' ),
				};
			}
		}

		return $atts ? ' ' . implode( ' ', $atts ) : '';
	}

	/**
	 * Flatten a multidimensional associative array with dots.
	 *
	 * ```php
	 * $array = [ 'products' => [ 'desk' => [ 'price' => 100 ] ] ];
	 * Arr::dot( $array );
	 * // [ 'products.desk.price' => 100 ]
	 * ```
	 *
	 * @param  iterable  $array
	 * @param  string  $prepend
	 * @return array
	 */
	public static function dot( $array, $prepend = '' ): array {
		$results = [];

		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				if ( is_array( $value ) && ! empty( $value ) ) {
					$results = array_merge( $results, static::dot( $value, $prepend . $key . '.' ) );
				} else {
					$results[ $prepend . $key ] = $value;
				}
			}
		}

		return $results;
	}

	/**
	 * Convert flatten "dot" notation array into an expanded array.
	 *
	 * ```php
	 * $array = [ 'user.name' => 'Kevin Malone', 'user.occupation' => 'Accountant' ];
	 * print_r( Arr::undot( $array ) );
	 * // [ 'user' => [ 'name' => 'Kevin Malone', 'occupation' => 'Accountant' ] ]
	 * ```
	 *
	 * @param  iterable  $array
	 * @return array
	 */
	public static function undot( $array ) {
		$results = [];
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				static::set( $results, $key, $value );
			}
		}
		return $results;
	}

	/**
	 * Set an array item to a given value using "dot" notation.
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * ```php
	 * $array = [ 'products' => [ 'desk' => [ 'price' => 100 ] ] ];
	 * Arr::set( $array, 'products.desk.price', 200 );
	 * // [ 'products' => [ 'desk' => [ 'price' => 200 ] ] ]
	 * ```
	 *
	 * @param  array  $array
	 * @param  string|int|null  $key
	 * @param  mixed  $value
	 * @return array
	 */
	public static function set( &$array, $key, $value ): array {
		if ( is_null( $key ) ) {
			return $array = $value;
		}

		$keys = explode( '.', $key );

		foreach ( $keys as $i => $key ) {
			if ( count( $keys ) === 1 ) {
				break;
			}

			unset( $keys[ $i ] );

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
				$array[ $key ] = [];
			}

			$array = &$array[ $key ];
		}

		$array[ array_shift( $keys ) ] = $value;

		return $array;
	}

	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * ```php
	 * $array = [ 'products' => [ 'desk' => [ 'price' => 100 ] ] ];
	 * Arr::get( $array, 'products.desk.price' );
	 * // 100
	 * ```
	 *
	 * @param  array  $array
	 * @param  string|int|null  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function get( array $array, string|int|null $key, mixed $default = null ): mixed {
		if ( is_null( $key ) ) {
			return $array;
		}

		if ( array_key_exists( $key, $array ) ) {
			return $array[ $key ];
		}

		if ( ! strpos( $key, '.' ) ) {
			return $array[ $key ] ?? $default;
		}

		$segments = explode( '.', $key );
		foreach ( $segments as $segment ) {
			if ( is_array( $array ) ) {
				if ( isset( $array[ $segment ] ) ) {
					$array = $array[ $segment ];
				} else {
					return $default;
				}
			}
		}
		return $array;
	}
}
