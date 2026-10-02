<?php

declare(strict_types=1);

namespace Expansa\Webauthn\Internal;

use Expansa\Codecs\Base64;
use Expansa\Webauthn\Exceptions\InvalidCredential;

/**
 * Client data the browser collected for the authenticator: ceremony type, challenge and origin.
 * Parsing checks only the form; RelyingParty compares the values with the ceremony.
 *
 * @internal
 */
final readonly class ClientData
{
    private function __construct(

        /**
         * JSON as the browser sent it, its hash is part of the signed data.
         */
        public string $raw,

        /**
         * `webauthn.create` or `webauthn.get`.
         */
        public string $type,

        /**
         * Raw challenge the browser answers.
         */
        public string $challenge,

        /**
         * Origin of the page that ran the ceremony.
         */
        public string $origin,

        /**
         * Whether the ceremony ran in a cross-origin iframe.
         */
        public bool $crossOrigin,
    ) {}

    /**
     * Parse the client data JSON.
     *
     * @param string $raw
     * @return self
     * @throws InvalidCredential If it isn't an object with a type, challenge and origin.
     */
    public static function parse(string $raw): self
    {
        $data = json_decode($raw, true, 4);

        if (
            ! is_array($data)
            || ! is_string($data['type'] ?? null)
            || ! is_string($data['challenge'] ?? null)
            || ! is_string($data['origin'] ?? null)
        ) {
            throw new InvalidCredential('Malformed client data.');
        }

        $challenge = new Base64()->decode($data['challenge']) ?? '';
        if ($challenge === '') {
            throw new InvalidCredential('Malformed client data challenge.');
        }

        return new self($raw, $data['type'], $challenge, $data['origin'], ($data['crossOrigin'] ?? false) === true);
    }
}
