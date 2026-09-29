<?php

declare(strict_types=1);

namespace Expansa\Webauthn\Internal;

use Expansa\Webauthn\Exceptions\InvalidCredential;

/**
 * Credential public key: converts a COSE key (RFC 9053) to PEM and verifies signatures with it.
 * ES256 and RS256 go through OpenSSL, EdDSA (Ed25519) through Sodium.
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
                => hex2bin(self::P256_PREFIX) . "\x04" . $cose[-2] . $cose[-3],
            $algorithm === self::EDDSA && ($cose[1] ?? null) === 1 && ($cose[-1] ?? null) === 6 && strlen($cose[-2] ?? '') === 32
                => hex2bin(self::ED25519_PREFIX) . $cose[-2],
            $algorithm === self::RS256 && ($cose[1] ?? null) === 3 && is_string($cose[-1] ?? null) && is_string($cose[-2] ?? null)
                => self::sequence(hex2bin(self::RSA_ALGORITHM) . self::bitString(self::sequence(self::integer($cose[-1]) . self::integer($cose[-2])))),
            default => throw new InvalidCredential('Malformed public key.'),
        };

        return [$algorithm, "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n"];
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
