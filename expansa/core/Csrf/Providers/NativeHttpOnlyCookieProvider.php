<?php

declare(strict_types=1);

namespace Expansa\Csrf\Providers;

use Expansa\Csrf\Interfaces\Provider;

class NativeHttpOnlyCookieProvider implements Provider
{
    const HOUR_IN_SECONDS = 3600;

    /**
     * Get a cookie value.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $_COOKIE[$key] ?? null;
    }

    /**
     * Set a cookie value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        setcookie($key, $value, time() + self::HOUR_IN_SECONDS, '/', '', false, true);
    }
}
