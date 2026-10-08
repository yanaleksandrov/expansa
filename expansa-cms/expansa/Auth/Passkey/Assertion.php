<?php

declare(strict_types=1);

namespace Expansa\Auth\Passkey;

use Expansa\Auth\Exceptions\InvalidCredential;
use Expansa\Auth\Internal\AuthenticatorData;
use Expansa\Auth\Internal\ClientData;
use Expansa\Auth\Internal\Payload;

/**
 * Sign-in response of `navigator.credentials.get()`, parsed but not verified: the ID finds
 * the stored credential, the challenge the pending ceremony, then Passkey::authenticate() checks everything.
 */
final readonly class Assertion
{
    private function __construct(

        /**
         * Raw credential ID.
         */
        public string $id,

        /**
         * Raw challenge the browser answers, not yet verified.
         */
        public string $challenge,

        /**
         * User handle the authenticator keeps with a discoverable credential, empty if it sent none.
         */
        public string $userHandle,

        /**
         * Parsed client data.
         *
         * @internal Checked by Passkey.
         */
        public ClientData $clientData,

        /**
         * Parsed authenticator data.
         *
         * @internal Checked by Passkey.
         */
        public AuthenticatorData $authenticatorData,

        /**
         * Signature over the authenticator data and the client data hash.
         *
         * @internal Verified by Passkey.
         */
        public string $signature,
    ) {}

    /**
     * Parse `PublicKeyCredential.toJSON()` of get().
     *
     * @param string $json
     * @return self
     * @throws InvalidCredential If the response is malformed.
     */
    public static function parse(string $json): self
    {
        $payload    = Payload::parse($json);
        $clientData = ClientData::parse($payload->bytes('clientDataJSON'));

        return new self(
            $payload->id,
            $clientData->challenge,
            $payload->optional('userHandle') ?? '',
            $clientData,
            AuthenticatorData::parse($payload->bytes('authenticatorData')),
            $payload->bytes('signature'),
        );
    }
}
