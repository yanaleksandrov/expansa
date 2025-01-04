<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 412 Precondition Failed responses
 *
 * @package Requests\Exceptions
 */
final class Status412 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 412;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_412;
}
