<?php

declare(strict_types=1);

namespace Expansa\Auth;

use Expansa\Auth\Exceptions\InvalidCredential;
use Expansa\Auth\Internal\AuthenticatorData;
use Expansa\Auth\Internal\ClientData;
use Expansa\Auth\Internal\PublicKey;
use Expansa\Auth\Passkey\Assertion;
use Expansa\Auth\Passkey\Attestation;
use Expansa\Auth\Passkey\Credential;
use Expansa\Codecs\Base64;

/**
 * WebAuthn relying party (W3C Web Authentication, level 3): builds ceremony options for
 * `navigator.credentials.create()`/`get()` and verifies the parsed responses.
 *
 * Passkeys only: credentials are discoverable, so a sign-in needs neither a login nor a password.
 * User verification (fingerprint, face, PIN) is asked for when the authenticator supports it and
 * demanded only with $requireUserVerification. Both ceremonies hint the device's own authenticator,
 * so browsers open its prompt instead of a QR code for a phone. Attestation isn't requested and its
 * statement isn't evaluated: the credential is trusted because the signed-in user registered it,
 * not because of its maker.
 *
 * Challenges and credential storage are the caller's: generate 32 random bytes, keep them server-side
 * until the response arrives and never accept one twice; store the Credential and replace it with the
 * one authenticate() returns.
 *
 * ```php
 * $passkey   = new Passkey('example.com', 'https://example.com', 'Example');
 * $options   = $passkey->requestOptions($challenge);       // to the browser
 * $assertion = Assertion::parse($json);                    // find the challenge and credential by it
 * $stored    = $passkey->authenticate($assertion, $challenge, $stored);
 * ```
 */
final readonly class Passkey
{
    /**
     * URL-safe Base64 for binary fields of the options.
     */
    private Base64 $base64;

    public function __construct(

        /**
         * RP ID: the host credentials are bound to, e.g. `example.com`.
         */
        public string $id,

        /**
         * The only origin accepted in client data, e.g. `https://example.com`.
         */
        public string $origin,

        /**
         * Site name the browser shows in its prompt.
         */
        public string $name,

        /**
         * Prompt timeout in milliseconds.
         */
        public int $timeout = 60000,

        /**
         * Reject authenticators that only prove presence (a touch) without verifying the user.
         */
        public bool $requireUserVerification = false,
    ) {
        $this->base64 = new Base64();
    }

    /**
     * Options for registering a passkey, in the JSON shape of `PublicKeyCredentialCreationOptions`.
     *
     * @param string       $challenge   Random bytes.
     * @param string       $userHandle  Stable opaque account ID, up to 64 bytes, not an email or login.
     * @param string       $userName    Account name shown by the authenticator.
     * @param string       $displayName Human name shown by the authenticator.
     * @param Credential[] $exclude     Credentials of the account, so an authenticator isn't added twice.
     * @return array<string, mixed>
     */
    public function creationOptions(string $challenge, string $userHandle, string $userName, string $displayName, array $exclude = []): array
    {
        return [
            'rp'                     => ['id' => $this->id, 'name' => $this->name],
            'user'                   => ['id' => $this->encode($userHandle), 'name' => $userName, 'displayName' => $displayName],
            'challenge'              => $this->encode($challenge),
            'pubKeyCredParams'       => array_map(fn (int $alg) => ['type' => 'public-key', 'alg' => $alg], PublicKey::algorithms()),
            'timeout'                => $this->timeout,
            'attestation'            => 'none',
            'hints'                  => ['client-device'],
            'authenticatorSelection' => ['residentKey' => 'required', 'requireResidentKey' => true, 'userVerification' => $this->userVerification()],
            'excludeCredentials'     => array_map(fn (Credential $credential) => [
                'type'       => 'public-key',
                'id'         => $this->encode($credential->id),
                'transports' => $credential->transports,
            ], $exclude),
        ];
    }

    /**
     * Options for signing in, in the JSON shape of `PublicKeyCredentialRequestOptions`.
     * No credentials are listed: the authenticator offers the passkeys it holds for the RP ID.
     *
     * @param string $challenge Random bytes.
     * @return array<string, mixed>
     */
    public function requestOptions(string $challenge): array
    {
        return [
            'challenge'        => $this->encode($challenge),
            'rpId'             => $this->id,
            'timeout'          => $this->timeout,
            'userVerification' => $this->userVerification(),
            'hints'            => ['client-device'],
            'allowCredentials' => [],
        ];
    }

    /**
     * Verify a registration response and create the credential to store.
     *
     * @param Attestation $attestation Parsed response of create().
     * @param string      $challenge   The challenge of the creation options.
     * @param string      $userHandle  The user handle of the creation options.
     * @return Credential
     * @throws InvalidCredential If any check fails.
     */
    public function register(Attestation $attestation, string $challenge, string $userHandle): Credential
    {
        $data = $attestation->authenticatorData;

        $this->checkClientData($attestation->clientData, 'webauthn.create', $challenge);
        $this->checkAuthenticatorData($data);

        [$algorithm, $publicKey] = PublicKey::fromCose((array) $data->publicKey);

        return new Credential(
            $attestation->id,
            $userHandle,
            $algorithm,
            $publicKey,
            $data->counter,
            $attestation->transports,
            $data->has(AuthenticatorData::BACKUP_ELIGIBLE),
            $data->has(AuthenticatorData::BACKED_UP),
        );
    }

    /**
     * Verify a sign-in response against the stored credential.
     *
     * @param Assertion  $assertion Parsed response of get().
     * @param string     $challenge The challenge of the request options.
     * @param Credential $stored    Credential found by the assertion ID.
     * @return Credential The credential with the new counter and backup state, to store.
     * @throws InvalidCredential If any check fails or the counter went back (a cloned authenticator).
     */
    public function authenticate(Assertion $assertion, string $challenge, Credential $stored): Credential
    {
        $data = $assertion->authenticatorData;

        if (! hash_equals($stored->id, $assertion->id)) {
            throw new InvalidCredential('The response belongs to another credential.');
        }

        $this->checkClientData($assertion->clientData, 'webauthn.get', $challenge);
        $this->checkAuthenticatorData($data);

        // a discoverable credential identifies the account by itself, so the handle is mandatory
        if (! hash_equals($stored->userHandle, $assertion->userHandle)) {
            throw new InvalidCredential('The credential belongs to another account.');
        }

        if ($data->has(AuthenticatorData::BACKUP_ELIGIBLE) !== $stored->backupEligible) {
            throw new InvalidCredential('The backup eligibility of the credential changed.');
        }

        $signed = $data->raw . hash('sha256', $assertion->clientData->raw, true);
        if (! PublicKey::verify($stored->algorithm, $stored->publicKey, $signed, $assertion->signature)) {
            throw new InvalidCredential('Invalid signature.');
        }

        if (($data->counter !== 0 || $stored->counter !== 0) && $data->counter <= $stored->counter) {
            throw new InvalidCredential('The signature counter did not increase: the authenticator may be cloned.');
        }

        return new Credential(
            $stored->id,
            $stored->userHandle,
            $stored->algorithm,
            $stored->publicKey,
            $data->counter,
            $stored->transports,
            $stored->backupEligible,
            $data->has(AuthenticatorData::BACKED_UP),
        );
    }

    /**
     * Check the ceremony type, challenge and origin the browser signed.
     *
     * @param ClientData $clientData
     * @param string     $type       `webauthn.create` or `webauthn.get`.
     * @param string     $challenge  Expected raw challenge.
     * @return void
     * @throws InvalidCredential If the client data belongs to another ceremony or origin.
     */
    private function checkClientData(ClientData $clientData, string $type, string $challenge): void
    {
        if (
            $clientData->type !== $type
            || $challenge === ''
            || ! hash_equals($challenge, $clientData->challenge)
            || $clientData->origin !== $this->origin
            || $clientData->crossOrigin
        ) {
            throw new InvalidCredential('The client data does not match the ceremony.');
        }
    }

    /**
     * Check that the authenticator scoped the credential to this RP, the user was there
     * and the backup flags agree.
     *
     * @param AuthenticatorData $data
     * @return void
     * @throws InvalidCredential If a check fails.
     */
    private function checkAuthenticatorData(AuthenticatorData $data): void
    {
        if (! hash_equals(hash('sha256', $this->id, true), $data->rpIdHash)) {
            throw new InvalidCredential('The credential is bound to another RP ID.');
        }

        if (! $data->has(AuthenticatorData::USER_PRESENT)) {
            throw new InvalidCredential('The user was not present.');
        }

        if ($this->requireUserVerification && ! $data->has(AuthenticatorData::USER_VERIFIED)) {
            throw new InvalidCredential('The user was not verified.');
        }

        if ($data->has(AuthenticatorData::BACKED_UP) && ! $data->has(AuthenticatorData::BACKUP_ELIGIBLE)) {
            throw new InvalidCredential('A credential that is not backup eligible cannot be backed up.');
        }
    }

    /**
     * User verification requirement of the options.
     *
     * @return string `required` or `preferred`.
     */
    private function userVerification(): string
    {
        return $this->requireUserVerification ? 'required' : 'preferred';
    }

    /**
     * Encode binary as unpadded base64url.
     *
     * @param string $bytes
     * @return string
     */
    private function encode(string $bytes): string
    {
        return $this->base64->encode($bytes, true);
    }
}
