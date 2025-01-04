<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 304 Not Modified responses
 *
 * @package Requests\Exceptions
 */
final class Status304 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 304;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_304;
}
