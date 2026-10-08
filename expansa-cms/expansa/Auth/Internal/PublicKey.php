<?php

declare(strict_types=1);

namespace Expansa\Auth\Internal;

use Expansa\Auth\Exceptions\InvalidCredential;
use Expansa\Auth\Exceptions\InvalidToken;
use Expansa\Codecs\Base64;

/**
 * Signing public key of a passkey (COSE, RFC 9053) or of an identity token issuer (JWK, RFC 7517):
 * converts it to PEM and verifies signatures. ES256 and RS256 go through OpenSSL, EdDSA through Sodium.
 *
 * @internal
 */
final class PublicKey
{
    /**
     * COSE algorithm IDs.
     */
    public const int EDDSA = -8;
    public const int ES256 = -7;
    public const int RS256 = -257;

    /**
     * DER prefix of a P-256 SubjectPublicKeyInfo, followed by the uncompressed point.
     */
    private const string P256_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    /**
     * DER prefix of an Ed25519 SubjectPublicKeyInfo, followed by the 32-byte key.
     */
    private const string ED25519_PREFIX = '302a300506032b6570032100';

    /**
     * DER AlgorithmIdentifier of rsaEncryption.
     */
    private const string RSA_ALGORITHM = '300d06092a864886f70d0101010500';

    /**
     * Algorithms this server can verify, in order of preference.
     *
     * @return int[]
     */
    public static function algorithms(): array
    {
        return function_exists('sodium_crypto_sign_verify_detached')
            ? [self::EDDSA, self::ES256, self::RS256]
            : [self::ES256, self::RS256];
    }

    /**
     * Convert a COSE key map to PEM.
     *
     * @param array<int, mixed> $cose
     * @return array{int, string} Algorithm ID and PEM.
     * @throws InvalidCredential If the key type, curve or algorithm is unsupported.
     */
    public static function fromCose(array $cose): array
    {
        $algorithm = $cose[3] ?? null;
        if (! in_array($algorithm, self::algorithms(), true)) {
            throw new InvalidCredential('Unsupported public key algorithm.');
        }

        // kty: 1 OKP, 2 EC2, 3 RSA; crv: 1 P-256, 6 Ed25519
        $der = match (true) {
            $algorithm === self::ES256 && ($cose[1] ?? null) === 2 && ($cose[-1] ?? null) === 1
                && strlen($cose[-2] ?? '') === 32 && strlen($cose[-3] ?? '') === 32
                => self::p256($cose[-2], $cose[-3]),
            $algorithm === self::EDDSA && ($cose[1] ?? null) === 1 && ($cose[-1] ?? null) === 6 && strlen($cose[-2] ?? '') === 32
                => hex2bin(self::ED25519_PREFIX) . $cose[-2],
            $algorithm === self::RS256 && ($cose[1] ?? null) === 3 && is_string($cose[-1] ?? null) && is_string($cose[-2] ?? null)
                => self::rsa($cose[-1], $cose[-2]),
            default => throw new InvalidCredential('Malformed public key.'),
        };

        return [$algorithm, self::pem($der)];
    }

    /**
     * Convert a JSON Web Key (RFC 7517) of an RS256 or ES256 signing key to PEM.
     *
     * @param array<string, mixed> $jwk
     * @return array{int, string} COSE algorithm ID and PEM.
     * @throws InvalidToken If the key type, curve or algorithm is unsupported.
     */
    public static function fromJwk(array $jwk): array
    {
        $base64 = new Base64();

        [$n, $e, $x, $y] = array_map(
            fn (string $name) => is_string($jwk[$name] ?? null) ? (string) $base64->decode($jwk[$name]) : '',
            ['n', 'e', 'x', 'y'],
        );

        // "alg" is optional in a key set, the key type decides then
        return match (true) {
            ($jwk['kty'] ?? null) === 'RSA' && ($jwk['alg'] ?? 'RS256') === 'RS256' && $n !== '' && $e !== ''
                => [self::RS256, self::pem(self::rsa($n, $e))],
            ($jwk['kty'] ?? null) === 'EC' && ($jwk['crv'] ?? null) === 'P-256' && ($jwk['alg'] ?? 'ES256') === 'ES256'
                && strlen($x) === 32 && strlen($y) === 32
                => [self::ES256, self::pem(self::p256($x, $y))],
            default => throw new InvalidToken('Unsupported signing key.'),
        };
    }

    /**
     * Convert a raw ECDSA signature `r || s` of JWS to the DER form verify() takes.
     *
     * @param string $raw 64 bytes for P-256.
     * @return string Empty if the length is wrong.
     */
    public static function derSignature(string $raw): string
    {
        if (strlen($raw) !== 64) {
            return '';
        }

        return self::sequence(self::integer(substr($raw, 0, 32)) . self::integer(substr($raw, 32)));
    }

    /**
     * Verify a signature made by the credential.
     *
     * @param int    $algorithm COSE algorithm ID.
     * @param string $pem       Public key.
     * @param string $data      Signed data.
     * @param string $signature DER signature for ES256, raw for RS256 and EdDSA.
     * @return bool
     */
    public static function verify(int $algorithm, string $pem, string $data, string $signature): bool
    {
        if ($algorithm === self::EDDSA) {
            $key = substr((string) base64_decode(preg_replace('/-----[^-]+-----|\s/', '', $pem)), -32);

            return function_exists('sodium_crypto_sign_verify_detached')
                && strlen($signature) === 64
                && sodium_crypto_sign_verify_detached($signature, $data, $key);
        }

        return openssl_verify($data, $signature, $pem, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * DER SubjectPublicKeyInfo of a P-256 point.
     *
     * @param string $x 32 bytes.
     * @param string $y 32 bytes.
     * @return string
     */
    private static function p256(string $x, string $y): string
    {
        return hex2bin(self::P256_PREFIX) . "\x04" . $x . $y;
    }

    /**
     * DER SubjectPublicKeyInfo of an RSA key.
     *
     * @param string $modulus  Unsigned big-endian bytes.
     * @param string $exponent Unsigned big-endian bytes.
     * @return string
     */
    private static function rsa(string $modulus, string $exponent): string
    {
        return self::sequence(hex2bin(self::RSA_ALGORITHM) . self::bitString(self::sequence(self::integer($modulus) . self::integer($exponent))));
    }

    /**
     * PEM armor of a DER key.
     *
     * @param string $der
     * @return string
     */
    private static function pem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /**
     * DER SEQUENCE.
     *
     * @param string $content Encoded elements.
     * @return string
     */
    private static function sequence(string $content): string
    {
        return "\x30" . self::length(strlen($content)) . $content;
    }

    /**
     * DER BIT STRING without unused bits.
     *
     * @param string $content
     * @return string
     */
    private static function bitString(string $content): string
    {
        return "\x03" . self::length(strlen($content) + 1) . "\x00" . $content;
    }

    /**
     * DER INTEGER from unsigned big-endian bytes.
     *
     * @param string $bytes
     * @return string
     */
    private static function integer(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || ord($bytes[0]) & 0x80) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . self::length(strlen($bytes)) . $bytes;
    }

    /**
     * DER length: short form below 128, long form above.
     *
     * @param int $length
     * @return string
     */
    private static function length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($bytes)) . $bytes;
    }
}
