<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 405 Method Not Allowed responses
 *
 * @package Requests\Exceptions
 */
final class Status405 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 405;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_405;
}
