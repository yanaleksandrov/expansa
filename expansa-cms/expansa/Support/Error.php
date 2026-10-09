<?php

declare(strict_types=1);

namespace Expansa\Support;

use JsonSerializable;

/**
 * Error with a code, returned instead of a result: `return new Error('media_upload', '...')`.
 * The caller checks it with `instanceof Error`. Each instance keeps only its own messages.
 *
 * @package Expansa\Support
 */
final class Error implements JsonSerializable
{
    /**
     * Messages of the error; validator errors keep their field keys.
     *
     * @var array<array-key, mixed>
     */
    public private(set) array $messages = [];

    /**
     * Create an error with the first message.
     *
     * @param string|array<array-key, mixed> $message Single message or a list, e.g. validator errors.
     */
    public function __construct(

        /**
         * Error code: `user-signin`.
         */
        public readonly string $code,

        /**
         * Error message.
         */
        string|array $message = '',
    ) {
        $this->add($message);
    }

    /**
     * Append a message or a list of messages; an empty string adds nothing.
     *
     * @param string|array<array-key, mixed> $message
     * @return static
     */
    public function add(string|array $message): static
    {
        if (is_array($message)) {
            $this->messages = array_merge($this->messages, $message);
        } elseif ($message !== '') {
            $this->messages[] = $message;
        }

        return $this;
    }

    /**
     * Code and messages, the shape of the error in API responses.
     *
     * @return array{code: string, message: array<array-key, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'code'    => $this->code,
            'message' => $this->messages,
        ];
    }
}
