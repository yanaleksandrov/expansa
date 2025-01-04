<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 503 Service Unavailable responses
 *
 * @package Requests\Exceptions
 */
final class Status503 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 503;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_503;
}
