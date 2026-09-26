<?php

declare(strict_types=1);

namespace Expansa\Security;

use Expansa\Security\Csrf\Contracts\Provider;
use Expansa\Security\Csrf\Providers\Cookie;
use Expansa\Security\Exceptions\InvalidCsrfTokenException;
use Random\RandomException;

/**
 * CSRF tokens: generates a token, stores it with a provider and checks the submitted one.
 * A token holds its creation time and a hash of the client IP and user agent, so it expires
 * and works only for the client it was issued to.
 *
 * ```php
 * $csrf  = new Csrf(new Csrf\Providers\Session());
 * $token = $csrf->generate('profile');
 *
 * try {
 *     $csrf->check('profile', $_POST['token'] ?? '', timespan: 3600);
 * } catch (InvalidCsrfTokenException $e) {
 *     return $e->getMessage();
 * }
 * ```
 *
 * @package Expansa\Security
 */
final class Csrf
{
    public function __construct(

        /**
         * Token storage; by default an HttpOnly cookie that lives one hour.
         */
        private Provider $provider = new Cookie(httpOnly: true),

        /**
         * Prefix of the storage keys, the cookie name is the prefix plus the token key.
         */
        private string $prefix = 'expansa_',
    ) {}

    /**
     * Generate a token and store it under the key.
     *
     * @param string $key
     * @return string
     * @throws RandomException If there is no source of randomness.
     */
    public function generate(string $key): string
    {
        $token = $this->createToken();

        $this->provider->set($this->prefix . $this->sanitizeKey($key), $token);

        return $token;
    }

    /**
     * Check the submitted token against the stored one; a valid one-time token is replaced with a new one.
     *
     * @param string   $key
     * @param string   $token    Submitted token.
     * @param int|null $timespan Lifetime of the token in seconds, `null` — no expiration.
     * @param bool     $multiple Keep the token for more requests, useful for AJAX-heavy pages.
     * @return void
     * @throws InvalidCsrfTokenException If the token is missing, forged, of another client or expired.
     */
    public function check(string $key, string $token, ?int $timespan = null, bool $multiple = false): void
    {
        $key = $this->prefix . $this->sanitizeKey($key);

        if ($token === '') {
            throw new InvalidCsrfTokenException('Invalid CSRF token');
        }

        $stored = $this->provider->get($key);
        if (! $stored) {
            throw new InvalidCsrfTokenException('Invalid CSRF session token');
        }

        $decoded = base64_decode($stored);
        if (! hash_equals($this->referralHash(), substr($decoded, 10, 40)) || ! hash_equals($stored, $token)) {
            throw new InvalidCsrfTokenException('Invalid CSRF token');
        }

        if ($timespan !== null && (int) substr($decoded, 0, 10) + $timespan < time()) {
            throw new InvalidCsrfTokenException('CSRF token has expired');
        }

        // a new token, not the same one: expiration slides with the embedded time
        if (! $multiple) {
            try {
                $this->provider->set($key, $this->createToken());
            } catch (RandomException) {
                // the current token is still valid
            }
        }
    }

    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9]+/', '', $key);
    }

    /**
     * Create a token: time, referral hash and a random part.
     *
     * @return string
     * @throws RandomException
     */
    private function createToken(): string
    {
        return base64_encode(time() . $this->referralHash() . sha1(random_bytes(32)));
    }

    /**
     * Hash of the client IP and user agent.
     *
     * @return string
     */
    private function referralHash(): string
    {
        return sha1(($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}
