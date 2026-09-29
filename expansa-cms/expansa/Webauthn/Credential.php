<?php

declare(strict_types=1);

namespace Expansa\Webauthn;

/**
 * A registered credential as the relying party stores it: everything needed to verify
 * later sign-ins. The private key never leaves the authenticator.
 */
final readonly class Credential
{
    public function __construct(

        /**
         * Raw credential ID, up to 1023 bytes.
         */
        public string $id,

        /**
         * COSE algorithm ID of the key.
         */
        public int $algorithm,

        /**
         * Public key in PEM.
         */
        public string $publicKey,

        /**
         * Last seen signature counter.
         */
        public int $counter = 0,

        /**
         * How the browser can reach the authenticator: `internal`, `hybrid`, `usb`, `nfc`, `ble`.
         *
         * @var string[]
         */
        public array $transports = [],
    ) {}
}
