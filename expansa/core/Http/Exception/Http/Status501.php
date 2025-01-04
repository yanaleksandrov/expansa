<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 501 Not Implemented responses
 *
 * @package Requests\Exceptions
 */
final class Status501 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 501;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_501;
}
