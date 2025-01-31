<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\HttpStatuses;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 429 Too Many Requests responses
 *
 * @link https://tools.ietf.org/html/draft-nottingham-http-new-status-04
 *
 * @package Requests\Exceptions
 */
final class Status429 extends HttpStatuses
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 429;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_429;
}
