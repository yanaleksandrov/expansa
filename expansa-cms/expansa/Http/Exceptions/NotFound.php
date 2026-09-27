<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Throwable;

/**
 * Thrown when the requested resource does not exist: a 404 error response.
 *
 * @package Expansa\Http
 */
final class NotFound extends HttpError
{
    /**
     * @param string                $message
     * @param array<string, string> $headers
     * @param Throwable|null        $previous
     * @param int                   $code
     */
    public function __construct(string $message = '', array $headers = [], ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct(404, $message, $headers, $previous, $code);
    }
}
