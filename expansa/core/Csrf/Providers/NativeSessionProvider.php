<?php

declare(strict_types=1);

namespace Expansa\Csrf\Providers;

use Expansa\Csrf\Interfaces\Provider;

class NativeSessionProvider implements Provider
{
    /**
     * Get a session value.
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Set a session value.
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }
}
