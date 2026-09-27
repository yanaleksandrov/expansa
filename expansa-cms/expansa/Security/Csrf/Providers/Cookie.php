<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf\Providers;

use Expansa\Security\Csrf\Contracts\Provider;

/**
 * Keeps tokens in cookies of the whole site, readable by JavaScript for the double-submit pattern.
 *
 * @package Expansa\Security
 */
final class Cookie implements Provider
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
        setcookie($key, $token, time() + $this->lifetime, '/', '');
    }
}
