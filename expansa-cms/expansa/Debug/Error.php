<?php

declare(strict_types=1);

namespace Expansa\Debug;

/**
 * Errors class.
 *
 * A class whose task is to simplify error handling (messages) when working with Expansa. In order
 * to start working with a class, you need to create an instance of it, and then add or remove
 * errors (messages) to it. The class applies the $message and $data parameters.
 *
 * These are strings and in general they are similar, but they separated by logic:
 *   $message - is a message for the user.
 *   $data    - is an instruction for the developer.
 */
class Error
{
    /**
     * Errors list storage.
     *
     * @var array
     */
    private static array $errors = [];

    /**
     * Add an error or append additional message to an existing error.
     *
     * @param string $code          Error code.
     * @param string|array $message Error single message or array of messages.
     */
    public function __construct(string $code, string|array $message = '')
    {
        if (is_array($message)) {
            self::$errors[$code] = array_merge(self::$errors[$code] ?? [], $message);
        } else {
            self::$errors[$code][] = $message;
        }
    }

    /**
     * Removes the specified error.
     *
     * This function removes all error messages associated with the specified
     * error code, along with any error data for that code.
     *
     * @param string $code Errors code.
     */
    public function remove(string $code): void
    {
        unset(self::$errors[$code]);
    }

    /**
     * Retrieve all error messages or error messages matching code.
     *
     * @param string $code Optional. Retrieve messages matching code, if exists.
     * @return array Errors strings on success, or empty array on failure (if using code parameter).
     */
    public function get(string $code = ''): array
    {
        if (empty($code)) {
            return self::$errors;
        }
        return self::$errors[$code] ?? [];
    }

    /**
     * Verify if the instance contains errors.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return ! empty(self::$errors);
    }
}
