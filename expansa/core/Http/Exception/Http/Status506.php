<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 506 Variant Also Negotiates responses
 *
 * @package Requests\Exceptions
 */
final class Status506 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 506;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_506;
}
