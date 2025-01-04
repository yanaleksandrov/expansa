<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 502 Bad Gateway responses
 *
 * @package Requests\Exceptions
 */
final class Status502 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 502;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_502;
}
