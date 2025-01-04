<?php
/**
 * Requests for PHP, an HTTP library.
 *
 * @copyright 2012-2023 Requests Contributors
 * @license   https://github.com/WordPress/Requests/blob/stable/LICENSE ISC
 * @link      https://github.com/WordPress/Requests
 */

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 425 Too Early responses
 *
 * @package Requests\Exceptions
 */
final class Status425 extends Http {
	/**
	 * HTTP status code
	 *
	 * @var int
	 */
	protected $code = 425;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = HttpStatus::TEXT_425;
}
