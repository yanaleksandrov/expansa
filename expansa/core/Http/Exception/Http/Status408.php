<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 408 Request Timeout responses
 *
 * @package Requests\Exceptions
 */
final class Status408 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 408;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_408;
}
