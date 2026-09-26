<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf\Providers;

use Expansa\Security\Csrf\Contracts\Provider;

/**
 * Keeps tokens in $_SESSION, the session must be started.
 *
 * @package Expansa\Security
 */
final class Session implements Provider
{
    public function get(string $key): ?string
    {
        $token = $_SESSION[$key] ?? null;

        return is_string($token) ? $token : null;
    }

    public function set(string $key, string $token): void
    {
        $_SESSION[$key] = $token;
    }
}
