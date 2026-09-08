<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Throwable;

/**
 * Thrown when incoming request data fails validation. Kernel turns it into
 * a 422 JSON response carrying both the message and the per-field errors.
 */
final class ValidationException extends HttpException
{
    public function __construct(string $message, private readonly array $errors = [], ?Throwable $previous = null)
    {
        parent::__construct(422, $message, [], $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
