<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 423 Locked responses
 *
 * @package Requests\Exceptions
 */
final class Status423 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 423;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_423;
}
