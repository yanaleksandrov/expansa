<?php

declare(strict_types=1);

namespace Expansa\Auth\Passkey;

use Expansa\Auth\Exceptions\InvalidCredential;
use Expansa\Auth\Internal\AuthenticatorData;
use Expansa\Auth\Internal\Cbor;
use Expansa\Auth\Internal\ClientData;
use Expansa\Auth\Internal\Payload;

/**
 * Registration response of `navigator.credentials.create()`, parsed but not verified:
 * the challenge finds the pending ceremony, then Passkey::register() checks everything.
 */
final readonly class Attestation
{
    private function __construct(

        /**
         * Raw ID of the new credential.
         */
        public string $id,

        /**
         * Raw challenge the browser answers, not yet verified.
         */
        public string $challenge,

        /**
         * How the browser can reach the authenticator: `internal`, `hybrid`, `usb`, `nfc`, `ble`.
         *
         * @var string[]
         */
        public array $transports,

        /**
         * Parsed client data.
         *
         * @internal Checked by Passkey.
         */
        public ClientData $clientData,

        /**
         * Parsed authenticator data with the attested credential.
         *
         * @internal Checked by Passkey.
         */
        public AuthenticatorData $authenticatorData,
    ) {}

    /**
     * Parse `PublicKeyCredential.toJSON()` of create(). The attestation statement isn't kept:
     * none is requested, the credential is trusted because the signed-in user registers it.
     *
     * @param string $json
     * @return self
     * @throws InvalidCredential If the response is malformed or attests another credential.
     */
    public static function parse(string $json): self
    {
        $payload     = Payload::parse($json);
        $clientData  = ClientData::parse($payload->bytes('clientDataJSON'));
        $attestation = Cbor::decode($payload->bytes('attestationObject'));

        if (
            ! is_array($attestation)
            || ! is_string($attestation['fmt'] ?? null)
            || ! is_string($attestation['authData'] ?? null)
        ) {
            throw new InvalidCredential('Malformed attestation object.');
        }

        $data = AuthenticatorData::parse($attestation['authData']);
        if (! $data->has(AuthenticatorData::ATTESTED) || ! hash_equals($payload->id, (string) $data->credentialId)) {
            throw new InvalidCredential('The attested credential does not match the response.');
        }

        return new self($payload->id, $clientData->challenge, $payload->strings('transports'), $clientData, $data);
    }
}
