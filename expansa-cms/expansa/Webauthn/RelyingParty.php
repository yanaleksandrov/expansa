<?php

declare(strict_types=1);

namespace Expansa\Webauthn;

use Expansa\Codecs\Base64;
use Expansa\Webauthn\Exceptions\InvalidCredential;
use Expansa\Webauthn\Internal\AuthenticatorData;
use Expansa\Webauthn\Internal\Cbor;
use Expansa\Webauthn\Internal\PublicKey;

/**
 * WebAuthn relying party (W3C Web Authentication, level 3): builds ceremony options for
 * `navigator.credentials.create()`/`get()` and verifies what the browser returns.
 *
 * Passkeys only: credentials are discoverable, so a sign-in needs neither a login nor a password.
 * User verification (fingerprint, face, PIN) is asked for when the authenticator supports it and
 * demanded only with $requireUserVerification. Attestation isn't requested and its statement isn't evaluated:
 * the credential is trusted because the signed-in user registered it, not because of its maker.
 *
 * Challenges are the caller's: generate 32 random bytes, keep them server-side until the
 * response arrives and never accept one twice.
 *
 * ```php
 * $rp      = new RelyingParty('example.com', 'https://example.com', 'Example');
 * $options = $rp->requestOptions($challenge);            // to the browser
 * $counter = $rp->authenticate($credential, $rp->challenge($credential), $stored, $userHandle);
 * ```
 */
final readonly class RelyingParty
{
    /**
     * Longest credential ID the specification allows, in bytes.
     */
    private const int MAX_CREDENTIAL_ID = 1023;

    /**
     * URL-safe Base64 for binary fields of the JSON shapes.
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
     * @param string   $challenge   Random bytes.
     * @param string   $userHandle  Stable opaque account ID, up to 64 bytes, not an email or login.
     * @param string   $userName    Account name shown by the authenticator.
     * @param string   $displayName Human name shown by the authenticator.
     * @param string[] $exclude     Raw IDs of already registered credentials.
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
            'authenticatorSelection' => ['residentKey' => 'required', 'requireResidentKey' => true, 'userVerification' => $this->userVerification()],
            'excludeCredentials'     => array_map(fn (string $id) => ['type' => 'public-key', 'id' => $this->encode($id)], $exclude),
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
            'allowCredentials' => [],
        ];
    }

    /**
     * Raw ID of a browser credential, to find the stored one before authenticate().
     *
     * @param array<string, mixed> $credential `PublicKeyCredential.toJSON()`.
     * @return string
     * @throws InvalidCredential If the ID is missing or malformed.
     */
    public function credentialId(array $credential): string
    {
        $id = $this->decode($credential['rawId'] ?? null);
        if ($id === '' || strlen($id) > self::MAX_CREDENTIAL_ID || ($credential['type'] ?? null) !== 'public-key') {
            throw new InvalidCredential('Malformed credential ID.');
        }

        return $id;
    }

    /**
     * Challenge a browser credential answers, not yet verified: to find the pending one
     * among several (tabs, autofill) before register() or authenticate() check it.
     *
     * @param array<string, mixed> $credential `PublicKeyCredential.toJSON()`.
     * @return string Raw challenge, empty for a malformed credential.
     */
    public function challenge(array $credential): string
    {
        $response   = is_array($credential['response'] ?? null) ? $credential['response'] : [];
        $clientData = json_decode($this->decode($response['clientDataJSON'] ?? null), true);

        return $this->decode(is_array($clientData) ? $clientData['challenge'] ?? null : null);
    }

    /**
     * Verify a registration response and extract the credential to store.
     *
     * @param array<string, mixed> $credential `PublicKeyCredential.toJSON()` of create().
     * @param string               $challenge  The challenge of the creation options.
     * @return Credential
     * @throws InvalidCredential If any check fails.
     */
    public function register(array $credential, string $challenge): Credential
    {
        $id       = $this->credentialId($credential);
        $response = is_array($credential['response'] ?? null) ? $credential['response'] : [];

        $this->checkClientData($response, 'webauthn.create', $challenge);

        $attestation = Cbor::decode($this->decode($response['attestationObject'] ?? null));
        if (! is_array($attestation) || ! is_string($attestation['fmt'] ?? null) || ! is_string($attestation['authData'] ?? null)) {
            throw new InvalidCredential('Malformed attestation object.');
        }

        $data = AuthenticatorData::parse($attestation['authData']);
        $this->checkAuthenticatorData($data);

        if (! $data->has(AuthenticatorData::ATTESTED) || ! hash_equals($id, (string) $data->credentialId)) {
            throw new InvalidCredential('The attested credential does not match the response.');
        }

        [$algorithm, $publicKey] = PublicKey::fromCose((array) $data->publicKey);

        $transports = array_values(array_filter((array) ($response['transports'] ?? []), 'is_string'));

        return new Credential($id, $algorithm, $publicKey, $data->counter, $transports);
    }

    /**
     * Verify a sign-in response against the stored credential.
     *
     * @param array<string, mixed> $credential `PublicKeyCredential.toJSON()` of get().
     * @param string               $challenge  The challenge of the request options.
     * @param Credential           $stored     Credential found by credentialId().
     * @param string               $userHandle User handle of the credential owner.
     * @return int New signature counter to store.
     * @throws InvalidCredential If any check fails or the counter went back (a cloned authenticator).
     */
    public function authenticate(array $credential, string $challenge, Credential $stored, string $userHandle): int
    {
        if (! hash_equals($stored->id, $this->credentialId($credential))) {
            throw new InvalidCredential('The response belongs to another credential.');
        }

        $response   = is_array($credential['response'] ?? null) ? $credential['response'] : [];
        $clientData = $this->checkClientData($response, 'webauthn.get', $challenge);

        $raw  = $this->decode($response['authenticatorData'] ?? null);
        $data = AuthenticatorData::parse($raw);
        $this->checkAuthenticatorData($data);

        // a discoverable credential identifies the account by itself, so the handle is mandatory
        if (! hash_equals($userHandle, $this->decode($response['userHandle'] ?? null))) {
            throw new InvalidCredential('The credential belongs to another account.');
        }

        $signature = $this->decode($response['signature'] ?? null);
        if (! PublicKey::verify($stored->algorithm, $stored->publicKey, $raw . hash('sha256', $clientData, true), $signature)) {
            throw new InvalidCredential('Invalid signature.');
        }

        if (($data->counter !== 0 || $stored->counter !== 0) && $data->counter <= $stored->counter) {
            throw new InvalidCredential('The signature counter did not increase: the authenticator may be cloned.');
        }

        return $data->counter;
    }

    /**
     * Check the ceremony type, challenge and origin the browser signed.
     *
     * @param array<string, mixed> $response  Authenticator response.
     * @param string               $type      `webauthn.create` or `webauthn.get`.
     * @param string               $challenge Expected challenge.
     * @return string Raw client data JSON, its hash is part of the signed data.
     */
    private function checkClientData(array $response, string $type, string $challenge): string
    {
        $raw        = $this->decode($response['clientDataJSON'] ?? null);
        $clientData = json_decode($raw, true);

        if (
            ! is_array($clientData)
            || ($clientData['type'] ?? null) !== $type
            || ! is_string($clientData['challenge'] ?? null)
            || ! hash_equals($this->encode($challenge), $clientData['challenge'])
            || ($clientData['origin'] ?? null) !== $this->origin
            || ($clientData['crossOrigin'] ?? false) === true
        ) {
            throw new InvalidCredential('The client data does not match the ceremony.');
        }

        return $raw;
    }

    /**
     * Check that the authenticator scoped the credential to this RP and the user was there.
     *
     * @param AuthenticatorData $data
     * @return void
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

    /**
     * Decode a base64url field of the response, an empty string for a missing or malformed one.
     *
     * @param mixed $value
     * @return string
     */
    private function decode(mixed $value): string
    {
        return is_string($value) ? (string) $this->base64->decode($value) : '';
    }
}
