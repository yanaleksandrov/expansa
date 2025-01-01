<?php

namespace Grafema\Asset;

class Helpers
{

	/**
	 * Convert array to attributes.
	 *
	 * @param array $attributes
	 * @param array $whitelist
	 * @return string
	 * @since 2025.1
	 */
	public function format( array $attributes, array $whitelist ): string
	{
		$result = [];

		foreach ( $attributes as $attribute => $value ) {
			if ( ! in_array( $attribute, $whitelist, true ) ) {
				continue;
			}

			$attribute = trim( htmlspecialchars( $attribute, ENT_QUOTES, 'UTF-8' ) );
			$value     = trim( htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) );
			if ( $attribute ) {
				if ( in_array( $attribute, ['async', 'defer'], true ) && ! empty( $value ) ) {
					$result[] = $attribute;
				}

				if ( $value ) {
					$result[] = sprintf( '%s="%s"', $attribute, $value );
				}
			}
		}

		return $result ? ' ' . implode( ' ', $result ) : '';
	}
}
