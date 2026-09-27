<?php

declare(strict_types=1);

namespace Expansa\Session\Contracts;

/**
 * Messages kept in the session until they are read, usually on the next request.
 *
 * @package Expansa\Session
 */
interface Flash
{
    /**
     * Append a message under the key.
     *
     * @param string $key
     * @param string $message
     * @return void
     */
    public function add(string $key, string $message): void;

    /**
     * Get the messages of the key and forget them.
     *
     * @param string $key
     * @return string[]
     */
    public function pull(string $key): array;

    /**
     * Get all messages grouped by key and forget them.
     *
     * @return array<string, string[]>
     */
    public function pullAll(): array;

    public function has(string $key): bool;

    /**
     * Replace the messages of the key.
     *
     * @param string   $key
     * @param string[] $messages
     * @return void
     */
    public function set(string $key, array $messages): void;

    /**
     * Forget all messages.
     *
     * @return void
     */
    public function flush(): void;
}
