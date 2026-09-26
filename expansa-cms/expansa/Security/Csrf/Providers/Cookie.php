<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf\Providers;

use Expansa\Security\Csrf\Contracts\Provider;

/**
 * Keeps tokens in cookies of the whole site.
 *
 * @package Expansa\Security
 */
final class Cookie implements Provider
{
    public function __construct(

        /**
         * Hide the cookie from JavaScript; a double-submit token read by scripts needs false.
         */
        public readonly bool $httpOnly = false,

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
        setcookie($key, $token, time() + $this->lifetime, '/', '', false, $this->httpOnly);
    }
}
