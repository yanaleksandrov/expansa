<?php

declare(strict_types=1);

namespace Expansa\Cookie;

use Expansa\Cookie\Contracts\Queue;
use Expansa\Cookie\Enums\SameSite;

/**
 * Creates cookies with the configured defaults and queues them for the response.
 * Target of the Cookie facade, so the defaults and the queue live for the whole request;
 * the application passes getQueue() to Response::setCookie() before sending.
 *
 * @package Expansa\Cookie
 */
final class CookieJar implements Queue
{
    /**
     * Minutes in about a year, the lifetime of createForever().
     */
    private const int FOREVER = 576000;

    /**
     * Default path.
     */
    public private(set) string $path = '/';

    /**
     * Default domain.
     */
    public private(set) string $domain = '';

    /**
     * Default secure attribute.
     */
    public private(set) bool $secure = false;

    /**
     * Default httpOnly attribute.
     */
    public private(set) bool $httpOnly = true;

    /**
     * Default SameSite attribute.
     */
    public private(set) SameSite $sameSite = SameSite::Lax;

    /**
     * Queued cookies by name, then by path.
     *
     * @var array<string, array<string, Cookie>>
     */
    private array $queue = [];

    public function configure(
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        SameSite $sameSite = SameSite::Lax,
    ): void {
        $this->path     = $path;
        $this->domain   = $domain;
        $this->secure   = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
    }

    public function create(
        string $name,
        string $value,
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?SameSite $sameSite = null,
    ): Cookie {
        return new Cookie(
            $name,
            $value,
            $minutes === 0 ? 0 : time() + $minutes * 60,
            $path ?? $this->path,
            $domain ?? $this->domain,
            $secure ?? $this->secure,
            $httpOnly ?? $this->httpOnly,
            $sameSite ?? $this->sameSite,
        );
    }

    public function createForever(
        string $name,
        string $value,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?SameSite $sameSite = null,
    ): Cookie {
        return $this->create($name, $value, self::FOREVER, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public function createExpired(string $name, ?string $path = null, ?string $domain = null): Cookie
    {
        return $this->create($name, '', 0, $path, $domain);
    }

    public function queue(
        Cookie|string $cookie,
        string $value = '',
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?SameSite $sameSite = null,
    ): void {
        if (is_string($cookie)) {
            $cookie = $this->create($cookie, $value, $minutes, $path, $domain, $secure, $httpOnly, $sameSite);
        }

        $this->queue[$cookie->name][$cookie->path] = $cookie;
    }

    public function hasQueued(string $name, ?string $path = null): bool
    {
        return $this->getQueued($name, $path) !== null;
    }

    public function getQueued(string $name, ?string $path = null): ?Cookie
    {
        if (! isset($this->queue[$name])) {
            return null;
        }

        return $path === null ? end($this->queue[$name]) : $this->queue[$name][$path] ?? null;
    }

    public function getQueue(): array
    {
        return array_merge(...array_values(array_map(array_values(...), $this->queue)));
    }

    public function forget(string $name, ?string $path = null): void
    {
        if ($path === null) {
            unset($this->queue[$name]);

            return;
        }

        unset($this->queue[$name][$path]);

        if (empty($this->queue[$name])) {
            unset($this->queue[$name]);
        }
    }

    public function expire(string $name, ?string $path = null, ?string $domain = null): void
    {
        $this->queue($this->createExpired($name, $path, $domain));
    }

    public function flush(): void
    {
        $this->queue = [];
    }
}
