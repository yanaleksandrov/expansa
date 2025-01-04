<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 306 Switch Proxy responses
 *
 * @package Requests\Exceptions
 */
final class Status306 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 306;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_306;
}
