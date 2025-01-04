<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 413 Request Entity Too Large responses
 *
 * @package Requests\Exceptions
 */
final class Status413 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 413;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_413;
}
