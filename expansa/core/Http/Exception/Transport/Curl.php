<?php
/**
 * Requests for PHP, an HTTP library.
 *
 * @copyright 2012-2023 Requests Contributors
 * @license   https://github.com/WordPress/Requests/blob/stable/LICENSE ISC
 * @link      https://github.com/WordPress/Requests
 */

namespace Expansa\Http\Exception\Transport;

use Expansa\Http\Exception\Transport;

/**
 * CURL Transport Exception.
 *
 * @package Requests\Exceptions
 */
final class Curl extends Transport {

	const EASY  = 'cURLEasy';
	const MULTI = 'cURLMulti';
	const SHARE = 'cURLShare';

	/**
	 * cURL error code
	 *
	 * @var int
	 */
	protected $code = -1;

	/**
	 * Which type of cURL error
	 *
	 * EASY|MULTI|SHARE
	 *
	 * @var string
	 */
	protected $type = 'Unknown';

	/**
	 * Clear text error message
	 *
	 * @var string
	 */
	protected $reason = 'Unknown';

	/**
	 * Create a new exception.
	 *
	 * @param string $message Exception message.
	 * @param string $type    Exception type.
	 * @param mixed  $data    Associated data, if applicable.
	 * @param int    $code    Exception numerical code, if applicable.
	 */
	public function __construct($message, $type, $data = null, $code = 0) {
		if ($type !== null) {
			$this->type = $type;
		}

		if ($code !== null) {
			$this->code = (int) $code;
		}

		if ($message !== null) {
			$this->reason = $message;
		}

		$message = sprintf('%d %s', $this->code, $this->reason);
		parent::__construct($message, $this->type, $data, $this->code);
	}

	/**
	 * Get the error message.
	 *
	 * @return string
	 */
	public function getReason() {
		return $this->reason;
	}
}
