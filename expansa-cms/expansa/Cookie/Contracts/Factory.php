<?php

declare(strict_types=1);

namespace Expansa\Cookie\Contracts;

use Expansa\Cookie\Cookie;
use Expansa\Cookie\Enums\SameSite;

/**
 * Creates cookies, filling the attributes left null with the configured defaults.
 *
 * @package Expansa\Cookie
 */
interface Factory
{
    /**
     * Default path.
     */
    public string $path { get; }

    /**
     * Default domain.
     */
    public string $domain { get; }

    /**
     * Default secure attribute.
     */
    public bool $secure { get; }

    /**
     * Default httpOnly attribute.
     */
    public bool $httpOnly { get; }

    /**
     * Default SameSite attribute.
     */
    public SameSite $sameSite { get; }

    /**
     * Set the defaults, a repeated call replaces all of them.
     *
     * @param string   $path
     * @param string   $domain
     * @param bool     $secure
     * @param bool     $httpOnly
     * @param SameSite $sameSite
     * @return void
     */
    public function configure(
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        SameSite $sameSite = SameSite::Lax,
    ): void;

    /**
     * Create a cookie that lives for the given minutes, 0 for a session cookie.
     *
     * @param string        $name
     * @param string        $value
     * @param int           $minutes
     * @param string|null   $path
     * @param string|null   $domain
     * @param bool|null     $secure
     * @param bool|null     $httpOnly
     * @param SameSite|null $sameSite
     * @return Cookie
     */
    public function create(
        string $name,
        string $value,
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?SameSite $sameSite = null,
    ): Cookie;

    /**
     * Create a cookie that lives about a year (576000 minutes).
     *
     * @param string        $name
     * @param string        $value
     * @param string|null   $path
     * @param string|null   $domain
     * @param bool|null     $secure
     * @param bool|null     $httpOnly
     * @param SameSite|null $sameSite
     * @return Cookie
     */
    public function createForever(
        string $name,
        string $value,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?SameSite $sameSite = null,
    ): Cookie;

    /**
     * Create a cookie that deletes the existing one in the browser.
     *
     * @param string      $name
     * @param string|null $path
     * @param string|null $domain
     * @return Cookie
     */
    public function createExpired(string $name, ?string $path = null, ?string $domain = null): Cookie;
}
