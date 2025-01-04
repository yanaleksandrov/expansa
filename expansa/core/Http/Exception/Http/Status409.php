<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 409 Conflict responses
 *
 * @package Requests\Exceptions
 */
final class Status409 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 409;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_409;
}
