<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 416 Requested Range Not Satisfiable responses
 *
 * @package Requests\Exceptions
 */
final class Status416 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 416;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_416;
}
