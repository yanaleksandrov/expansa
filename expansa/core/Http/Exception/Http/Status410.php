<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 410 Gone responses
 *
 * @package Requests\Exceptions
 */
final class Status410 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 410;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_410;
}
