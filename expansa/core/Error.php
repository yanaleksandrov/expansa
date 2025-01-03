<?php

declare(strict_types=1);

namespace Expansa;

/**
 * Error class.
 *
 * A class whose task is to simplify error handling (messages) when working with Expansa.
 * In order to start working with a class, you need to create an instance of it, and then add or remove errors (messages) to it.
 * The class applies the $message and $data parameters.
 *
 * These are strings and in general they are similar, but they separated by logic:
 *   $message - is a message for the user.
 *   $data    - is a instruction for the developer.
 */
final class Error extends Error\Handler
{
    /**
     * Initialize the error.
     *
     * If `$code` is empty, the other parameters will be ignored.
     * When `$code` is not empty, `$message` will be used even if it is empty.
     *
     * Though the class is constructed with a single error code and
     * message, multiple codes can be added using the `add()` method.
     *
     * @param string|int $code                Error code.
     * @param string|array|Validator $message Error single message or array of messages.
     */
    public function __construct(string|int $code, string|array|Validator $message = '')
    {
        self::add($code, $message);

        return $this;
    }

    /**
     * Add an error or append additional message to an existing error.
     *
     * @param string|int $code                Error code.
     * @param string|array|Validator $message Error single message or array of messages.
     * @return void
     */
    public static function add(string|int $code, string|array|Validator $message = ''): void
    {
        if ($message instanceof Validator) {
            foreach ($message->errors as $key => $errors) {
                (new Error\Handler())->push(sprintf('%s-%s', $code, $key), $errors);
            }
        } else {
            (new Error\Handler())->push($code, $message);
        }
    }

    /**
     * Removes the specified error.
     *
     * This function removes all error messages associated with the specified
     * error code, along with any error data for that code.
     *
     * @param string|int $code Error code.
     */
    public static function delete(string|int $code): void
    {
        (new Error\Handler())->remove($code);
    }

    /**
     * Verify if the instance contains errors.
     *
     * @return bool
     */
    public static function exists(): bool
    {
        return (new Error\Handler())->hasError();
    }

    /**
     * Retrieve all or single error messages.
     *
     * @param string|int $code Optional. Retrieve messages matching code, if exists.
     * @return array           Error strings on success, or empty array on failure (if using code parameter).
     */
    public static function get(string|int $code = ''): array
    {
        return (new Error\Handler())->getError($code);
    }

    /**
     * Retrieve all error codes.
     *
     * @return array List of error codes, if available.
     */
    public static function getCodes(): array
    {
        return (new Error\Handler())->getErrorCodes();
    }
}
