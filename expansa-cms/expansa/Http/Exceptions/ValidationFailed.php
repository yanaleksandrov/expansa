<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Expansa\Support\Error;
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

    /**
     * One message for one field, e.g. a refused password.
     *
     * @param string $field   Field name as the form sends it: `email`, `user.password` for `user[password]`.
     * @param string $message
     * @return self
     */
    public static function field(string $field, string $message): self
    {
        return new self($message, [$field => [$message]]);
    }

    /**
     * From an error a model returned: validator errors keep their fields, plain messages
     * go as a list the form shows apart from its fields.
     *
     * @param Error  $error
     * @param string $message Summary when the error has no plain message.
     * @return self
     */
    public static function from(Error $error, string $message): self
    {
        $first = array_values($error->messages)[0] ?? null;

        return new self(is_string($first) ? $first : $message, $error->messages);
    }
}
