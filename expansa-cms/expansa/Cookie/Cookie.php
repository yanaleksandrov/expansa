<?php

declare(strict_types=1);

namespace Expansa\Cookie;

use Expansa\Cookie\Exceptions\CookieException;

/**
 * HTTP cookie: validates its attributes and renders the Set-Cookie header value with __toString().
 * An empty value renders a header that deletes the cookie.
 *
 * @package Expansa\Cookie
 */
final class Cookie
{
    /**
     * Seconds until expiry, zero for an expired cookie.
     */
    public int $maxAge {
        get => max($this->expires - time(), 0);
    }

    public function __construct(

        /**
         * Cookie name: letters, digits and "._-".
         */
        public string $name {
            set {
                if (! preg_match('/^[A-Za-z0-9._-]+$/', $value)) {
                    throw new CookieException('The "name" parameter value contains illegal characters.');
                }

                $this->name = $value;
            }
        },

        /**
         * Cookie value, empty deletes the cookie.
         */
        public string $value = '',

        /**
         * Expiry as a Unix timestamp, 0 for a session cookie.
         */
        public int $expires = 0 {
            set => max($value, 0);
        },

        /**
         * URL path the cookie applies to, empty falls back to "/".
         */
        public string $path = '' {
            set => $value === '' ? '/' : $value;
        },

        /**
         * Domain the cookie applies to.
         */
        public string $domain = '',

        /**
         * Send the cookie over HTTPS only.
         */
        public bool $secure = false,

        /**
         * Hide the cookie from JavaScript.
         */
        public bool $httpOnly = false,

        /**
         * SameSite attribute, null to omit it.
         */
        public ?SameSite $sameSite = null,
    ) {}

    /**
     * Render the Set-Cookie header value, everything after "Set-Cookie: ".
     *
     * @return string
     */
    public function __toString(): string
    {
        $str = $this->name . '=';

        if ($this->value === '') {
            $str .= 'deleted; expires=' . gmdate('D, d M Y H:i:s T', time() - 31536001) . '; Max-Age=0';
        } else {
            $str .= rawurlencode($this->value);

            if ($this->expires > 0) {
                $str .= '; expires=' . gmdate('D, d M Y H:i:s T', $this->expires) . '; Max-Age=' . $this->maxAge;
            }
        }

        $str .= '; path=' . $this->path;

        if ($this->domain !== '') {
            $str .= '; domain=' . $this->domain;
        }

        if ($this->secure) {
            $str .= '; secure';
        }

        if ($this->httpOnly) {
            $str .= '; httponly';
        }

        if ($this->sameSite !== null) {
            $str .= '; samesite=' . $this->sameSite->value;
        }

        return $str;
    }

    /**
     * Whether the current request came over HTTPS, directly or through a proxy,
     * to set the secure attribute without hard-coding it.
     *
     * @return bool
     */
    public static function isSecureRequest(): bool
    {
        return (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Cookie sent by the client with the current request.
     *
     * @param string      $name
     * @param string|null $default
     * @return string|null
     */
    public static function get(string $name, ?string $default = null): ?string
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Send the cookie to the browser right away as a Set-Cookie header.
     *
     * @param Cookie $cookie
     * @return void
     */
    public static function send(Cookie $cookie): void
    {
        header('Set-Cookie: ' . $cookie, false);
    }
}
