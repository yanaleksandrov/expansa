<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Expansa\Http\Contracts\HttpException as HttpExceptionContract;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionContract
{
    public function __construct(
        protected int $statusCode,
        string $message = '',
        protected array $headers = [],
        ?Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
