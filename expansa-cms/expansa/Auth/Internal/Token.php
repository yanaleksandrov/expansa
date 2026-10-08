<?php

declare(strict_types=1);

namespace Expansa\Auth\Internal;

/**
 * Signed token `identifier|expires|session|hmac`: the HMAC covers the identifier, the expiry, the session ID
 * and a stamp, so a new stamp or key invalidates the token. The sign-in token is stamped with the user's stamp,
 * the device token with a constant and no session, so one can't pass for the other.
 *
 * @internal
 */
final readonly class Token
{
    private function __construct(

        /**
         * Raw token as it was parsed.
         */
        public string $raw,

        /**
         * Identifier of the user the token was issued to.
         */
        public string $identifier,

        /**
         * Unix time the token expires at.
         */
        public int $expires,

        /**
         * Session ID, empty without server-side sessions.
         */
        public string $session,

        /**
         * Hex HMAC-SHA256 signature.
         */
        private string $signature,
    ) {}

    /**
     * Sign a token.
     *
     * @param string $identifier
     * @param int    $expires    Unix time.
     * @param string $session    Session ID: letters and digits, may be empty.
     * @param string $stamp      Secret that changes when the token must stop working.
     * @param string $key        Secret key.
     * @return string
     */
    public static function sign(string $identifier, int $expires, string $session, string $stamp, string $key): string
    {
        $payload = $identifier . '|' . $expires . '|' . $session;

        return $payload . '|' . hash_hmac('sha256', $payload . '|' . $stamp, $key);
    }

    /**
     * Read a token without checking the signature, null if it is malformed or expired.
     *
     * @param string $token
     * @return self|null
     */
    public static function parse(string $token): ?self
    {
        // the identifier is matched greedily, so a "|" inside it can't shift the fields after it
        if (! preg_match('/^(.+)\|(\d+)\|([A-Za-z0-9]*)\|([a-f0-9]{64})$/', $token, $matches) || (int) $matches[2] < time()) {
            return null;
        }

        return new self($token, $matches[1], (int) $matches[2], $matches[3], $matches[4]);
    }

    /**
     * Check that the token was signed with the stamp and the key.
     *
     * @param string $stamp
     * @param string $key
     * @return bool
     */
    public function isSignedWith(string $stamp, string $key): bool
    {
        $payload = $this->identifier . '|' . $this->expires . '|' . $this->session;

        return hash_equals(hash_hmac('sha256', $payload . '|' . $stamp, $key), $this->signature);
    }
}
