<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\HttpStatuses;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 431 Request Header Fields Too Large responses
 *
 * @link https://tools.ietf.org/html/rfc6585
 *
 * @package Requests\Exceptions
 */
final class Status431 extends HttpStatuses
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 431;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_431;
}
