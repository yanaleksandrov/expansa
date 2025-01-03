<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf\Contracts;

interface SessionProvider
{
    /**
     * Get a session value.
     */
    public function get(string $key): mixed;

    /**
     * Set a session value.
     */
    public function set(string $key, mixed $value): void;
}
