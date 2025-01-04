<?php
/**
 * Requests for PHP, an HTTP library.
 *
 * @copyright 2012-2023 Requests Contributors
 * @license   https://github.com/WordPress/Requests/blob/stable/LICENSE ISC
 * @link      https://github.com/WordPress/Requests
 */

namespace Expansa\Http\Exception;

use InvalidArgumentException;

/**
 * Exception for an invalid argument passed.
 *
 * @package Requests\Exceptions
 * @since   2.0.0
 */
final class InvalidArgument extends InvalidArgumentException {

	/**
	 * Create a new invalid argument exception with a standardized text.
	 *
	 * @param int    $position The argument position in the function signature. 1-based.
	 * @param string $name     The argument name in the function signature.
	 * @param string $expected The argument type expected as a string.
	 * @param string $received The actual argument type received.
	 *
	 * @return \Expansa\Http\Exception\InvalidArgument
	 */
	public static function create($position, $name, $expected, $received) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		return new self(
			sprintf(
				'%s::%s(): Argument #%d (%s) must be of type %s, %s given',
				$stack[1]['class'],
				$stack[1]['function'],
				$position,
				$name,
				$expected,
				$received
			)
		);
	}
}
