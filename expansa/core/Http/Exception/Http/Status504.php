<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 504 Gateway Timeout responses
 *
 * @package Requests\Exceptions
 */
final class Status504 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 504;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_504;
}
