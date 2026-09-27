<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf\Providers;

use Expansa\Security\Csrf\Contracts\Provider;

/**
 * Keeps tokens in HttpOnly cookies of the whole site, hidden from JavaScript.
 *
 * @package Expansa\Security
 */
final class HttpOnlyCookie implements Provider
{
    public function __construct(

        /**
         * Cookie lifetime in seconds.
         */
        public readonly int $lifetime = 3600,
    ) {}

    public function get(string $key): ?string
    {
        $token = $_COOKIE[$key] ?? null;

        return is_string($token) ? $token : null;
    }

    public function set(string $key, string $token): void
    {
        setcookie($key, $token, time() + $this->lifetime, '/', '', false, true);
    }
}
