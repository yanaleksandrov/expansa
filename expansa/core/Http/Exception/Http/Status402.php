<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 402 Payment Required responses
 *
 * @package Requests\Exceptions
 */
final class Status402 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 402;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_402;
}
