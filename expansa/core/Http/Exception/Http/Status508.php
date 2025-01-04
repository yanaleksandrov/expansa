<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 508 Loop Detected responses
 *
 * @package Requests\Exceptions
 */
final class Status508 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 508;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_508;
}
