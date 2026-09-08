<?php

declare(strict_types=1);

namespace Expansa\Cookie;

use Expansa\Cookie\Exception\CookieException;

/**
 * A single HTTP cookie: validates its own name/sameSite, normalizes its own
 * expiry/path, and renders itself to a Set-Cookie header value via __toString().
 */
class Cookie
{
    /**
     * Cookie is not sent with cross-site requests at all.
     */
    public const string SAME_SITE_NONE = 'none';

    /**
     * Cookie is withheld only on cross-site subrequests (e.g. images, iframes).
     */
    public const string SAME_SITE_LAX = 'lax';

    /**
     * Cookie is never sent with any cross-site request.
     */
    public const string SAME_SITE_STRICT = 'strict';

    /**
     * Seconds remaining until expiry, floored at zero. Purely derived from
     * $expires, so it's a virtual property — nothing to store.
     */
    public int $maxAge {
        get => max($this->expires - time(), 0);
    }

    public function __construct(

        /**
         * Cookie name (letters, digits, "._-" only).
         */
        public string $name {
            set {
                if (! preg_match("/^([A-z0-9._-]+)$/i", $value)) {
                    throw new CookieException('The "name" parameter value contains illegal characters.');
                }
                $this->name = $value;
            }
        },

        /**
         * Cookie value; empty means "delete this cookie".
         */
        public string $value = '',

        /**
         * Expiry as a Unix timestamp, or 0 for a session cookie.
         */
        public int $expires = 0 {
            set => max($value, 0);
        },

        /**
         * URL path the cookie applies to; empty falls back to "/".
         */
        public string $path = '' {
            set => $value === '' ? '/' : $value;
        },

        /**
         * Domain the cookie applies to.
         */
        public string $domain = '',

        /**
         * Only send the cookie over HTTPS.
         */
        public bool $secure = false,

        /**
         * Hide the cookie from JavaScript.
         */
        public bool $httpOnly = false,

        /**
         * One of Cookie::SAME_SITE_*, or null to omit it.
         */
        public ?string $sameSite = null {
            set {
                if (! in_array($value, [self::SAME_SITE_NONE, self::SAME_SITE_LAX, self::SAME_SITE_STRICT, null], true)) {
                    throw new CookieException('The "sameSite" parameter value is not valid.');
                }
                $this->sameSite = $value;
            }
        }
    ) {} // phpcs:ignore

    /**
     * Render as a Set-Cookie header value (everything after "Set-Cookie: ").
     *
     * @return string
     */
    public function __toString(): string
    {
        $str = $this->name . '=';

        if (empty($this->value)) {
            $str .= 'deleted; expires=' . gmdate('D, d M Y H:i:s T', time() - 31536001) . '; Max-Age=0';
        } else {
            $str .= rawurlencode($this->value);

            if ($this->expires > 0) {
                $str .= '; expires=' . gmdate('D, d M Y H:i:s T', $this->expires) . '; Max-Age=' . $this->maxAge;
            }
        }

        if ($this->path) {
            $str .= '; path=' . $this->path;
        }

        if ($this->domain) {
            $str .= '; domain=' . $this->domain;
        }

        if ($this->secure) {
            $str .= '; secure';
        }

        if ($this->httpOnly) {
            $str .= '; httponly';
        }

        if ($this->sameSite !== null) {
            $str .= '; samesite=' . $this->sameSite;
        }

        return $str;
    }

    /**
     * Determine whether the current request is served over HTTPS, so the
     * "secure" attribute can be enabled without hard-coding it per call site.
     *
     * @return bool
     */
    public static function isSecureRequest(): bool
    {
        return (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) == 443
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Read a cookie the client sent with the current request.
     *
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Send a cookie to the browser right now, via a raw Set-Cookie header.
     *
     * Bypasses the CookieJar facade (Cookie::queue()): its queue survives
     * between calls, but nothing in the framework reads it back and turns it
     * into a Set-Cookie header, so a cookie handed to it would still silently
     * never reach the client.
     *
     * @param Cookie $cookie
     * @return void
     */
    public static function send(Cookie $cookie): void
    {
        header('Set-Cookie: ' . (string) $cookie, false);
    }
}
