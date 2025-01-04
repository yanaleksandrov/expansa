<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 510 Not Extended responses
 *
 * @package Requests\Exceptions
 */
final class Status510 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 510;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_510;
}
