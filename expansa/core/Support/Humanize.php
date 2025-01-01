<?php
namespace Expansa\Support;

/**
 *
 * @see https://github.com/mhanson01/humanize
 * @see https://github.com/coduo/php-humanizer
 * @see https://github.com/nFnK/php-humanizer
 * @since 2025.1
 */
final class Humanize {

	/**
	 * Convert bytes to human-readable file size.
	 *
	 * @param string|int $bytes
	 * @return string
	 * @since 2025.1
	 */
	public static function fromBytes( string|int $bytes ): string {
		if ( empty( $bytes ) ) {
			return '';
		}

		$i     = floor( log( $bytes ) / log( 1024 ) );
		$sizes = [ 'b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb' ];

		return sprintf( '%.02F', $bytes / pow( 1024, $i ) ) * 1 . ' ' . $sizes[ $i ];
	}

	/**
	 * Converts a human-readable file size value to a number of bytes that it represents.
	 * Supports the following modifiers: K, M, G and T.
	 * Invalid input is returned unchanged.
	 *
	 * Example:
	 * <code>
	 * $this->to_bytes(10);          // 10
	 * $this->to_bytes('10b');       // 10
	 * $this->to_bytes('10k');       // 10240
	 * $this->to_bytes('10K');       // 10240
	 * $this->to_bytes('10kb');      // 10240
	 * $this->to_bytes('10Kb');      // 10240
	 * // and even
	 * $this->to_bytes('   10 KB '); // 10240
	 * </code>
	 *
	 * @param string $value
	 * @return int
	 * @since 2025.1
	 */
	public static function toBytes( string $value ): int {
		return preg_replace_callback(
			'/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i',
			function ( $m ) {
				switch ( strtolower( $m[2] ) ) {
					case 't':
						$m[1] *= 1024;
					case 'g':
						$m[1] *= 1024;
					case 'm':
						$m[1] *= 1024;
					case 'k':
						$m[1] *= 1024;
				}
				return $m[1];
			},
			$value
		);
	}

	/**
	 * Converts an integer to a string containing commas every three digits.
	 *
	 * @param $int
	 * @return string
	 */
	public static function intcomma( $int ): string {
		return number_format( $int, 0, null, ',' );
	}

	/**
	 * Formats a number to a human-readable number.
	 *
	 * @param $number
	 * @return string
	 */
	public function formatnumber( $number ): string {
		return number_format( $number, 2, '.', ',' );
	}
}
