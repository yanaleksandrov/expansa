<?php

declare(strict_types=1);

namespace Expansa\Http\Contracts;

/**
 * Exception that becomes an HTTP error response.
 *
 * @package Expansa\Http\Contracts
 */
interface HttpException extends \Throwable
{
    /**
     * HTTP status code of the error response.
     */
    public int $statusCode { get; }

    /**
     * Extra response headers, header name => value.
     *
     * @var array<string, string>
     */
    public array $headers { get; }
}
