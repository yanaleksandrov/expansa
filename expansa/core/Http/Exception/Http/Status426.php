<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\Http;
use Expansa\Http\Utility\HttpStatus;

/**
 * Exception for 426 Upgrade Required responses
 *
 * @package Requests\Exceptions
 */
final class Status426 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 426;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = HttpStatus::TEXT_426;
}
