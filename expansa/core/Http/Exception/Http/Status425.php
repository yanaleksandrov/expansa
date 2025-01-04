<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 425 Too Early responses
 *
 * @package Requests\Exceptions
 */
final class Status425 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 425;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_425;
}
