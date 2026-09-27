<?php

declare(strict_types=1);

namespace Expansa\Http\Contracts;

use Throwable;

/**
 * Exception that becomes an HTTP error response.
 *
 * @package Expansa\Http
 */
interface Error extends Throwable
{
    /**
     * HTTP status code of the error response.
     *
     * @var int
     */
    public int $statusCode { get; }

    /**
     * Extra response headers, header name => value.
     *
     * @var array<string, string>
     */
    public array $headers { get; }
}
