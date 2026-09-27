<?php

declare(strict_types=1);

namespace Expansa\Cookie\Contracts;

use Expansa\Cookie\Cookie;
use Expansa\Cookie\Enums\SameSite;

/**
 * Collects cookies to send with the response, one per name and path.
 *
 * @package Expansa\Cookie
 */
interface Queue extends Factory
{
    /**
     * Queue a cookie, a string is its name and the other arguments go to create().
     * A cookie with the same name and path replaces the queued one.
     *
     * @param Cookie|string $cookie
     * @param string        $value
     * @param int           $minutes
     * @param string|null   $path
     * @param string|null   $domain
     * @param bool|null     $secure
     * @param bool|null     $httpOnly
     * @param SameSite|null $sameSite
     * @return void
     */
    public function queue(
        Cookie|string $cookie,
        string $value = '',
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?SameSite $sameSite = null,
    ): void;

    /**
     * Whether a cookie with the name, and the path if given, is queued.
     *
     * @param string      $name
     * @param string|null $path
     * @return bool
     */
    public function hasQueued(string $name, ?string $path = null): bool;

    /**
     * Queued cookie with the name at the path, or the last queued one with the name if no path is given.
     *
     * @param string      $name
     * @param string|null $path
     * @return Cookie|null
     */
    public function getQueued(string $name, ?string $path = null): ?Cookie;

    /**
     * All queued cookies.
     *
     * @return Cookie[]
     */
    public function getQueue(): array;

    /**
     * Take the cookie with the name out of the queue, at the path or at all paths.
     *
     * @param string      $name
     * @param string|null $path
     * @return void
     */
    public function forget(string $name, ?string $path = null): void;

    /**
     * Queue a cookie that deletes the existing one in the browser.
     *
     * @param string      $name
     * @param string|null $path
     * @param string|null $domain
     * @return void
     */
    public function expire(string $name, ?string $path = null, ?string $domain = null): void;

    /**
     * Empty the queue.
     *
     * @return void
     */
    public function flush(): void;
}
