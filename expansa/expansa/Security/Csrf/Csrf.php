<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf;

use Expansa\Security\Csrf\Contracts\SessionProvider;
use Expansa\Security\Csrf\Providers\NativeHttpOnlyCookieProvider;
use Expansa\Security\Exception\InvalidCsrfTokenException;
use Random\RandomException;

/**
 * CSRF (Cross-Site Request Forgery) token management class.
 *
 * This class provides functionality to generate and validate CSRF tokens. It uses
 * a session provider to store and retrieve tokens. The class includes methods for
 * generating tokens, checking token validity, and ensuring token expiration.
 *
 * $provider = new Csrf\Providers\NativeSessionProvider();
 * $csrf     = new Csrf\Csrf( $provider );
 *
 * $token = $csrf->generate( 'my_token' );
 *
 * try {
 *  $csrf->check( 'my_token', $token );
 * } catch ( Csrf\Exception\InvalidCsrfTokenException $e ) {
 *  return $e->getMessage();
 * }
 */
class Csrf
{
    /**
     * Csrf constructor.
     *
     * @param SessionProvider $session
     * @param string          $sessionPrefix Session prefix.
     */
    public function __construct(
        protected SessionProvider $session = new NativeHttpOnlyCookieProvider(),
        protected string $sessionPrefix = 'expansa_'
    ) {} // phpcs:ignore

    /**
     * Generate a CSRF token.
     *
     * @param string $key Key for this token
     * @return string|null
     * @throws RandomException
     */
    public function generate(string $key): ?string
    {
        $key = $this->sanitizeKey($key);

        try {
            $token = $this->createToken();
        } catch (InvalidCsrfTokenException $e) {
            return null;
        }

        $this->session->set($this->sessionPrefix . $key, $token);

        return $token;
    }

    /**
     * Check the CSRF token is valid.
     *
     * @param string   $key      Key for this token
     * @param string   $token    The token string (usually found in $_POST)
     * @param int|null $timespan Makes the token expire after $timespan seconds (null = never)
     * @param bool     $multiple Makes the token reusable and not one-time (Useful for ajax-heavy requests)
     *
     * @throws InvalidCsrfTokenException
     */
    public function check(string $key, string $token, int $timespan = null, bool $multiple = false): void
    {
        $key = $this->sanitizeKey($key);

        if (! $token) {
            throw new InvalidCsrfTokenException('Invalid CSRF token');
        }

        $sessionToken = $this->session->get($this->sessionPrefix . $key);
        if (! $sessionToken) {
            throw new InvalidCsrfTokenException('Invalid CSRF session token');
        }

        if (! $multiple) {
            $this->session->set($this->sessionPrefix . $key, $token);
        }

        if ($this->referralHash() !== substr(base64_decode($sessionToken), 10, 40)) {
            throw new InvalidCsrfTokenException('Invalid CSRF token');
        }

        if ($token !== $sessionToken) {
            throw new InvalidCsrfTokenException('Invalid CSRF token');
        }

        // check for token expiration
        if (is_int($timespan) && ( intval(substr(base64_decode($sessionToken), 0, 10)) + $timespan ) < time()) {
            throw new InvalidCsrfTokenException('CSRF token has expired');
        }
    }

    /**
     * Sanitizer the session key.
     */
    protected function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9]+/', '', $key);
    }

    /**
     * Create a new token. time() is used for token expiration.
     *
     * @throws RandomException
     */
    protected function createToken(): string
    {
        return base64_encode(time() . $this->referralHash() . $this->randomString());
    }

    /**
     * Return a unique referral hash.
     */
    protected function referralHash(): string
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return sha1($_SERVER['REMOTE_ADDR']);
        }

        return sha1($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Generate a random string.
     *
     * @throws RandomException
     */
    protected function randomString(): string
    {
        return sha1(random_bytes(32));
    }
}
