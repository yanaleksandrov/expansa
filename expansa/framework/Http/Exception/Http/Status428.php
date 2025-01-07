<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\HttpStatuses;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 428 Precondition Required responses
 *
 * @link https://tools.ietf.org/html/rfc6585
 *
 * @package Requests\Exceptions
 */
final class Status428 extends HttpStatuses
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 428;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_428;
}
