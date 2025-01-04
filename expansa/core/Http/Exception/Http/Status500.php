<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 500 Internal Server Error responses
 *
 * @package Requests\Exceptions
 */
final class Status500 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 500;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_500;
}
