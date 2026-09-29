<?php

declare(strict_types=1);

use Expansa\Webauthn\Credential;
use Expansa\Webauthn\Exceptions\InvalidCredential;
use Expansa\Webauthn\RelyingParty;

// run: php tests/Webauthn.php
require_once __DIR__ . '/bootstrap.php';

/**
 * Minimal CBOR encoder for the authenticator emulation: ints, byte strings, text keys, maps.
 *
 * @param mixed $value Binary strings are marked as ['bytes' => ...].
 * @return string
 */
function cbor(mixed $value): string
{
    $head = static function (int $major, int $argument): string {
        return match (true) {
            $argument < 24     => chr($major << 5 | $argument),
            $argument < 0x100  => chr($major << 5 | 24) . chr($argument),
            $argument < 0x10000 => chr($major << 5 | 25) . pack('n', $argument),
            default            => chr($major << 5 | 26) . pack('N', $argument),
        };
    };

    return match (true) {
        is_int($value)       => $value >= 0 ? $head(0, $value) : $head(1, -1 - $value),
        is_string($value)    => $head(3, strlen($value)) . $value,
        isset($value['bytes']) && count($value) === 1 => $head(2, strlen($value['bytes'])) . $value['bytes'],
        default              => $head(5, count($value)) . implode('', array_map(
            fn ($key, $item) => cbor($key) . cbor($item),
            array_keys($value),
            $value
        )),
    };
}

/**
 * Unpadded base64url.
 *
 * @param string $bytes
 * @return string
 */
function b64(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/**
 * Emulated authenticator holding one key pair.
 */
final class Authenticator
{
    public int $counter = 0;

    public string $credentialId;

    public function __construct(public string $privateKey, public int $algorithm)
    {
        $this->credentialId = random_bytes(16);
    }

    public function cose(): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey));

        return $this->algorithm === -7
            ? [1 => 2, 3 => -7, -1 => 1, -2 => ['bytes' => $details['ec']['x']], -3 => ['bytes' => $details['ec']['y']]]
            : [1 => 3, 3 => -257, -1 => ['bytes' => $details['rsa']['n']], -2 => ['bytes' => $details['rsa']['e']]];
    }

    public function authData(string $rpId, int $flags, bool $attested): string
    {
        $data = hash('sha256', $rpId, true) . chr($flags) . pack('N', $this->counter);

        return $attested
            ? $data . str_repeat("\0", 16) . pack('n', strlen($this->credentialId)) . $this->credentialId . cbor($this->cose())
            : $data;
    }

    public function create(string $rpId, string $origin, string $challenge, int $flags = 0x45): array
    {
        $clientData = json_encode(['type' => 'webauthn.create', 'challenge' => b64($challenge), 'origin' => $origin]);

        return [
            'id'       => b64($this->credentialId),
            'rawId'    => b64($this->credentialId),
            'type'     => 'public-key',
            'response' => [
                'clientDataJSON'    => b64($clientData),
                'attestationObject' => b64(cbor(['fmt' => 'none', 'attStmt' => [], 'authData' => ['bytes' => $this->authData($rpId, $flags, true)]])),
                'transports'        => ['internal'],
            ],
        ];
    }

    public function get(string $rpId, string $origin, string $challenge, string $userHandle, int $flags = 0x05): array
    {
        $this->counter++;
        $clientData = json_encode(['type' => 'webauthn.get', 'challenge' => b64($challenge), 'origin' => $origin]);
        $authData   = $this->authData($rpId, $flags, false);
        openssl_sign($authData . hash('sha256', $clientData, true), $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return [
            'id'       => b64($this->credentialId),
            'rawId'    => b64($this->credentialId),
            'type'     => 'public-key',
            'response' => [
                'clientDataJSON'    => b64($clientData),
                'authenticatorData' => b64($authData),
                'signature'         => b64($signature),
                'userHandle'        => b64($userHandle),
            ],
        ];
    }
}

$rp     = new RelyingParty('example.com', 'https://example.com', 'Example');
$handle = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

$options = $rp->creationOptions('challenge-bytes', $handle, 'admin', 'Admin', ['old-id']);
check('creation options require a discoverable credential', $options['authenticatorSelection'] === ['residentKey' => 'required', 'requireResidentKey' => true, 'userVerification' => 'preferred']);
check('creation options encode binary fields as base64url', $options['challenge'] === b64('challenge-bytes') && $options['user']['id'] === b64($handle) && $options['excludeCredentials'][0]['id'] === b64('old-id'));
check('request options list no credentials', $rp->requestOptions('c')['allowCredentials'] === [] && $rp->requestOptions('c')['rpId'] === 'example.com');

foreach (['ES256' => [__DIR__ . '/fixtures/webauthn/es256.pem', -7], 'RS256' => [__DIR__ . '/fixtures/webauthn/rs256.pem', -257]] as $name => [$file, $algorithm]) {
    $authenticator = new Authenticator(file_get_contents($file), $algorithm);

    $challenge  = random_bytes(32);
    $credential = $rp->register($authenticator->create('example.com', 'https://example.com', $challenge), $challenge);
    check("$name: register() returns the credential", $credential->id === $authenticator->credentialId && $credential->algorithm === $algorithm && $credential->transports === ['internal']);
    check("$name: the public key is valid PEM", openssl_pkey_get_public($credential->publicKey) !== false);

    $challenge = random_bytes(32);
    $response  = $authenticator->get('example.com', 'https://example.com', $challenge, $handle);
    check("$name: credentialId() decodes rawId", $rp->credentialId($response) === $authenticator->credentialId);
    check("$name: authenticate() returns the new counter", $rp->authenticate($response, $challenge, $credential, $handle) === 1);

    $stored = new Credential($credential->id, $credential->algorithm, $credential->publicKey, 1);
    check("$name: a replayed counter is rejected", throws(fn () => $rp->authenticate($response, $challenge, $stored, $handle), InvalidCredential::class));

    $challenge = random_bytes(32);
    $response  = $authenticator->get('example.com', 'https://example.com', $challenge, $handle);
    check("$name: another challenge is rejected", throws(fn () => $rp->authenticate($response, random_bytes(32), $stored, $handle), InvalidCredential::class));
    check("$name: another user handle is rejected", throws(fn () => $rp->authenticate($response, $challenge, $stored, 'other'), InvalidCredential::class));

    $tampered = $response;
    $tampered['response']['signature'] = b64(random_bytes(64));
    check("$name: a forged signature is rejected", throws(fn () => $rp->authenticate($tampered, $challenge, $stored, $handle), InvalidCredential::class));
    check("$name: a valid response passes", $rp->authenticate($response, $challenge, $stored, $handle) === 2);
}

$authenticator = new Authenticator(file_get_contents(__DIR__ . '/fixtures/webauthn/es256.pem'), -7);
$challenge     = random_bytes(32);
check('a foreign origin is rejected', throws(fn () => $rp->register($authenticator->create('example.com', 'https://evil.com', $challenge), $challenge), InvalidCredential::class));
check('a foreign RP ID is rejected', throws(fn () => $rp->register($authenticator->create('evil.com', 'https://example.com', $challenge), $challenge), InvalidCredential::class));
check('user verification is only preferred by default', $rp->requestOptions('c')['userVerification'] === 'preferred' && $rp->register($authenticator->create('example.com', 'https://example.com', $challenge, 0x41), $challenge) instanceof Credential);
check('a missing user presence is rejected', throws(fn () => $rp->register($authenticator->create('example.com', 'https://example.com', $challenge, 0x44), $challenge), InvalidCredential::class));

$strict = new RelyingParty('example.com', 'https://example.com', 'Example', requireUserVerification: true);
check('required user verification is announced', $strict->creationOptions('c', $handle, 'a', 'A')['authenticatorSelection']['userVerification'] === 'required');
check('a missing required user verification is rejected', throws(fn () => $strict->register($authenticator->create('example.com', 'https://example.com', $challenge, 0x41), $challenge), InvalidCredential::class));
check('challenge() reads the answered challenge', $rp->challenge($authenticator->create('example.com', 'https://example.com', $challenge)) === $challenge && $rp->challenge(['response' => 'x']) === '');

$response = $authenticator->create('example.com', 'https://example.com', $challenge);
$response['rawId'] = b64('other-id');
check('an attested ID other than rawId is rejected', throws(fn () => $rp->register($response, $challenge), InvalidCredential::class));

$response = $authenticator->create('example.com', 'https://example.com', $challenge);
$response['response']['attestationObject'] = b64("\xbf\xff");
check('malformed CBOR is rejected', throws(fn () => $rp->register($response, $challenge), InvalidCredential::class));
check('a credential without rawId is rejected', throws(fn () => $rp->credentialId(['type' => 'public-key']), InvalidCredential::class));

exit($failures > 0 ? 1 : 0);
