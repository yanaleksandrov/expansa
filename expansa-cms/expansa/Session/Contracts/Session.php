<?php

declare(strict_types=1);

namespace Expansa\Session\Contracts;

/**
 * Session data: values by key and flash messages.
 *
 * @package Expansa\Session
 */
interface Session
{
    /**
     * Flash messages stored in this session.
     */
    public Flash $flash { get; }

    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get all values.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    public function set(string $key, mixed $value): void;

    /**
     * Set several values at once.
     *
     * @param array<string, mixed> $values
     * @return void
     */
    public function setValues(array $values): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    /**
     * Forget all values, flash messages included.
     *
     * @return void
     */
    public function flush(): void;
}
