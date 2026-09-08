<?php

declare(strict_types=1);

namespace Expansa\Cookie;

use Expansa\Cookie\Contracts\QueueingFactory;
use Expansa\Cookie\Exception\CookieException;

/**
 * Builds and queues outgoing cookies. Exposed as the `Cookie` facade, which
 * resolves a single shared instance per request (see Expansa\Patterns\Facade),
 * so the queue and the configured defaults below persist across Cookie::* calls.
 */
class CookieJar implements QueueingFactory
{
    /**
     * Default path applied to a cookie that doesn't specify its own.
     */
    protected string $path = '/';

    /**
     * Default domain applied to a cookie that doesn't specify its own.
     */
    protected string $domain = '';

    /**
     * Default "secure" flag applied to a cookie that doesn't specify its own.
     */
    protected bool $secure = false;

    /**
     * Default "httpOnly" flag applied to a cookie that doesn't specify its own.
     */
    protected bool $httpOnly = true;

    /**
     * Cookies queued for later delivery, keyed by name then path.
     *
     * @var array<string, array<string, Cookie>>
     */
    protected array $queued = [];

    /**
     * Default "sameSite" attribute applied to a cookie that doesn't specify its own.
     *
     * @throws CookieException
     */
    protected string $sameSite = Cookie::SAME_SITE_LAX {
        set {
            if (! in_array($value, [Cookie::SAME_SITE_NONE, Cookie::SAME_SITE_LAX, Cookie::SAME_SITE_STRICT], true)) {
                throw new CookieException('The "sameSite" parameter value is not valid.');
            }
            $this->sameSite = $value;
        }
    }

    /**
     * Build a cookie, falling back to the configured defaults for any
     * attribute left null.
     *
     * @throws CookieException
     */
    public function make(
        string $name,
        string $value,
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null
    ): Cookie {
        $expires = $minutes === 0 ? 0 : time() + ($minutes * 60);

        return new Cookie(
            $name,
            $value,
            $expires,
            $path ?? $this->path,
            $domain ?? $this->domain,
            $secure ?? $this->secure,
            $httpOnly ?? $this->httpOnly,
            $sameSite ?? $this->sameSite,
        );
    }

    /**
     * Build a cookie that lasts roughly a year (576000 minutes).
     *
     * @throws CookieException
     */
    public function forever(
        string $name,
        string $value,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        bool $httpOnly = true,
        ?string $sameSite = null
    ): Cookie {
        return $this->make($name, $value, 576000, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * Build a cookie that clears an existing one (empty value, expired in the past).
     *
     * @throws CookieException
     */
    public function forget(string $name, ?string $path = null, ?string $domain = null): Cookie
    {
        return $this->make($name, '', -2628000, $path, $domain);
    }

    /**
     * Check whether a cookie with the given name (and, optionally, path) is queued.
     */
    public function hasQueued(string $name, ?string $path = null): bool
    {
        return $this->queued($name, null, $path) !== null;
    }

    /**
     * Queue a cookie (built from raw arguments, or already built) for later delivery.
     *
     * @throws CookieException
     */
    public function queue(
        Cookie|string $cookie,
        string $value = '',
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        bool $httpOnly = true,
        ?string $sameSite = null
    ): void {
        if (is_string($cookie)) {
            $cookie = $this->make($cookie, $value, $minutes, $path, $domain, $secure, $httpOnly, $sameSite);
        }

        $this->queued[$cookie->name][$cookie->path] = $cookie;
    }

    /**
     * Read a queued cookie by name — the one at the given path, or the most
     * recently queued one for that name if no path is given.
     *
     * @param mixed|null $default
     * @return mixed|Cookie
     */
    public function queued(string $name, mixed $default = null, ?string $path = null): mixed
    {
        if (! isset($this->queued[$name])) {
            return $default;
        }

        $queued = $this->queued[$name];

        if ($path === null) {
            return end($queued);
        }

        return $queued[$path] ?? $default;
    }

    /**
     * Remove a queued cookie (a specific path, or every path for that name).
     */
    public function unqueue(string $name, ?string $path = null): void
    {
        if ($path === null) {
            unset($this->queued[$name]);

            return;
        }

        unset($this->queued[$name][$path]);

        if (empty($this->queued[$name])) {
            unset($this->queued[$name]);
        }
    }

    /**
     * Queue a cookie that clears an existing one. Shorthand for queue(forget(...)).
     *
     * @throws CookieException
     */
    public function expire(string $name, ?string $path = null, ?string $domain = null): void
    {
        $this->queue($this->forget($name, $path, $domain));
    }

    /**
     * All queued cookies, flattened into a single list.
     *
     * @return Cookie[]
     */
    public function getQueuedCookies(): array
    {
        $cookies = [];

        foreach ($this->queued as $paths) {
            foreach ($paths as $cookie) {
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }

    /**
     * Empty the queue without sending anything.
     */
    public function flushQueuedCookies(): static
    {
        $this->queued = [];

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getSecure(): bool
    {
        return $this->secure;
    }

    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Change the defaults applied to cookies that don't specify their own
     * attributes. Only non-null arguments are updated.
     *
     * @throws CookieException
     */
    public function setDefault(
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null
    ): static {
        if ($path !== null) {
            $this->path = $path;
        }
        if ($domain !== null) {
            $this->domain = $domain;
        }
        if ($secure !== null) {
            $this->secure = $secure;
        }
        if ($httpOnly !== null) {
            $this->httpOnly = $httpOnly;
        }
        if ($sameSite !== null) {
            $this->sameSite = $sameSite;
        }

        return $this;
    }
}
