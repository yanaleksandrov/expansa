<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf\Providers;

use Expansa\Security\Csrf\Contracts\SessionProvider;

class NativeSessionProvider implements SessionProvider
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
