<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Throwable;

/**
 * Thrown when incoming request data fails validation. Kernel turns it into
 * a 422 JSON response carrying both the message and the per-field errors.
 *
 * @package Expansa\Http
 */
final class ValidationFailed extends HttpError
{
    public function __construct(

        string $message,

        /**
         * Validator errors (field => messages) or a plain list of messages; sent as "errors" in the 422 response.
         *
         * @var array<array-key, string|string[]>
         */
        public readonly array $errors = [],

        ?Throwable $previous = null,
    ) {
        parent::__construct(422, $message, [], $previous);
    }
}
