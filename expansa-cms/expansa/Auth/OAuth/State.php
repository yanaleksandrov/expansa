<?php

declare(strict_types=1);

namespace Expansa\Auth\OAuth;

use Expansa\Auth\Exceptions\InvalidState;
use Expansa\Codecs\Base64;

/**
 * Secrets of one sign-in attempt between the redirect and the callback: the `state` parameter
 * against CSRF, the PKCE verifier against a stolen code and the OpenID nonce against a replayed
 * token. Keep it server-side (the session), take it out at the callback and never reuse it.
 *
 * ```php
 * $state = State::create('google');
 * Session::set('oauth', $state->toArray());
 * // callback
 * $state = State::fromArray(Session::pull('oauth'));
 * ```
 */
final readonly class State
{
    /**
     * Seconds a state stays valid: the user has this long to answer the consent page.
     */
    public const int TTL = 600;

    public function __construct(

        /**
         * Name of the provider the attempt was started with.
         */
        public string $provider,

        /**
         * Value of the `state` parameter.
         */
        public string $value,

        /**
         * PKCE code verifier.
         */
        public string $verifier,

        /**
         * OpenID Connect nonce.
         */
        public string $nonce,

        /**
         * Unix time the attempt started.
         */
        public int $createdAt,
    ) {}

    /**
     * Start an attempt with fresh random secrets.
     *
     * @param string $provider
     * @return self
     */
    public static function create(string $provider): self
    {
        $base64 = new Base64();

        return new self(
            $provider,
            $base64->encode(random_bytes(32), true),
            $base64->encode(random_bytes(32), true),
            $base64->encode(random_bytes(16), true),
            time(),
        );
    }

    /**
     * Restore a state kept with toArray().
     *
     * @param mixed $data Value from the session, anything else than an array of toArray() is rejected.
     * @return self
     * @throws InvalidState If there is no state or it is malformed.
     */
    public static function fromArray(mixed $data): self
    {
        if (
            ! is_array($data)
            || ! is_string($data['provider'] ?? null)
            || ! is_string($data['value'] ?? null)
            || ! is_string($data['verifier'] ?? null)
            || ! is_string($data['nonce'] ?? null)
            || ! is_int($data['created_at'] ?? null)
        ) {
            throw new InvalidState('The sign-in attempt was not found.');
        }

        return new self($data['provider'], $data['value'], $data['verifier'], $data['nonce'], $data['created_at']);
    }

    /**
     * Plain array for the session.
     *
     * @return array{provider: string, value: string, verifier: string, nonce: string, created_at: int}
     */
    public function toArray(): array
    {
        return [
            'provider'   => $this->provider,
            'value'      => $this->value,
            'verifier'   => $this->verifier,
            'nonce'      => $this->nonce,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Whether the attempt is older than TTL.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return time() - $this->createdAt > self::TTL;
    }
}
