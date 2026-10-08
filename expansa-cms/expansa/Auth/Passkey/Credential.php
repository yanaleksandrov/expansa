<?php

declare(strict_types=1);

namespace Expansa\Auth\Passkey;

/**
 * A registered credential as the relying party stores it: everything needed to verify
 * later sign-ins. The private key never leaves the authenticator.
 *
 * Passkey::register() creates it, authenticate() returns a copy with the new counter
 * and backup state: store all fields and replace them after each sign-in.
 */
final readonly class Credential
{
    public function __construct(

        /**
         * Raw credential ID, up to 1023 bytes.
         */
        public string $id,

        /**
         * User handle of the owner the credential was registered for.
         */
        public string $userHandle,

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

        /**
         * Whether the credential may be synced to other devices; fixed at registration.
         */
        public bool $backupEligible = false,

        /**
         * Whether the credential was synced at the last ceremony.
         */
        public bool $backedUp = false,
    ) {}
}
