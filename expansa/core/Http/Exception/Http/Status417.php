<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 417 Expectation Failed responses
 *
 * @package Requests\Exceptions
 */
final class Status417 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 417;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_417;
}
