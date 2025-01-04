<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 403 Forbidden responses
 *
 * @package Requests\Exceptions
 */
final class Status403 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 403;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_403;
}
