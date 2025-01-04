<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 421 Misdirected Request responses
 *
 * @package Requests\Exceptions
 */
final class Status421 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 421;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_421;
}
