<?php

declare(strict_types=1);

namespace Expansa\Codec;

/**
 * Class Json
 *
 * Provides methods for encoding and decoding JSON data.
 * This class is intended for handling JSON serialization and deserialization
 * with options for customization, such as pretty printing and handling
 * character encoding. It ensures that JSON data is processed correctly
 * while providing meaningful error handling.
 *
 * @package Expansa\Codec
 */
class Json
{
	/**
	 * Converts value to JSON format.
	 *
	 * @param mixed $value
	 * @param bool $ascii        For ASCII output and $html_safe for HTML escaping.
	 * @param bool $pretty       For easier reading and clarity.
	 * @param bool $forceObjects Enforces the encoding of non-associative arrays as objects.
	 * @return string
	 */
	public function encode( mixed $value, bool $ascii = false, bool $pretty = false, bool $forceObjects = false ): string {
		$flags = JSON_UNESCAPED_SLASHES                  // do not escape slashes by default
			| ( $ascii ? 0 : JSON_UNESCAPED_UNICODE )    // keep unicode unescaped if $ascii = false
			| ( $pretty ? JSON_PRETTY_PRINT : 0 )        // pretty print
			| ( $forceObjects ? JSON_FORCE_OBJECT : 0 ); // convert arrays to objects if specified

		$json = json_encode( $value, $flags );

		// check for encoding errors
		if (json_last_error() !== JSON_ERROR_NONE) {
			return '';
		}

		return $json;
	}

	/**
	 * Parses JSON to PHP value.
	 *
	 * @param string $json
	 * @param bool $forceArrays Enforces the decoding of objects as arrays.
	 * @return mixed
	 */
	public function decode( string $json, bool $forceArrays = false ): mixed {
		$flags  = $forceArrays ? JSON_OBJECT_AS_ARRAY : 0;
		$flags |= JSON_BIGINT_AS_STRING;

		$value = json_decode($json, flags: $flags);

		// check for decoding errors
		if (json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}
		return $value;
	}

	/**
	 * Check that incoming data is valid json.
	 *
	 * @param mixed $data
	 * @return bool
	 */
	public function isValid(mixed $data): bool {
		return json_validate($data);
	}
}
