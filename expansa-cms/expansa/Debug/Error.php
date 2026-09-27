<?php

declare(strict_types=1);

namespace Expansa\Debug;

use JsonSerializable;

/**
 * Error messages by code, returned instead of a result: `return new Error('media_upload', t('...'))`.
 * Messages are kept in a registry shared by all instances for the request; an instance serializes
 * only its own code.
 *
 * @package Expansa\Debug
 */
final class Error implements JsonSerializable
{
    /**
     * Messages of all errors by code.
     *
     * @var array<string, string[]>
     */
    private static array $errors = [];

    /**
     * Add an error or append additional message to an existing error.
     */
    public function __construct(

        /**
         * Error code. Kept so jsonSerialize() reflects only this error, not the whole registry in self::$errors.
         */
        private string $code,

        /**
         * Error single message or array of messages.
         */
        string|array $message = '',
    )
    {
        if (is_array($message)) {
            self::$errors[$code] = array_merge(self::$errors[$code] ?? [], $message);
        } else {
            self::$errors[$code][] = $message;
        }
    }

    /**
     * Forget all messages of an error code.
     *
     * @param string $code
     * @return void
     */
    public function forget(string $code): void
    {
        unset(self::$errors[$code]);
    }

    /**
     * Get the messages of an error code, or of all codes without one.
     *
     * @param string $code
     * @return array
     */
    public function get(string $code = ''): array
    {
        return $code === '' ? self::$errors : self::$errors[$code] ?? [];
    }

    /**
     * Check if any error was added.
     *
     * @return bool
     */
    public function has(): bool
    {
        return ! empty(self::$errors);
    }

    /**
     * Specify the data that should be serialized to JSON — just this
     * instance's own code and messages, not the whole shared registry.
     *
     * @return array{code: string, message: array}
     */
    public function jsonSerialize(): array
    {
        return [
            'code'    => $this->code,
            'message' => $this->get($this->code),
        ];
    }
}
