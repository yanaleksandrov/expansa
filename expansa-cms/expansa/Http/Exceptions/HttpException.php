<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Expansa\Http\Contracts\HttpException as HttpExceptionContract;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionContract
{
    public function __construct(

        /**
         * HTTP status code of the error response, e.g. 404 or 409.
         */
        public readonly int $statusCode,

        string $message = '',

        /**
         * Extra response headers, header name => value.
         *
         * @var array<string, string>
         */
        public readonly array $headers = [],

        ?Throwable $previous = null,

        int $code = 0,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
