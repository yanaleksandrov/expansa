<?php

declare(strict_types=1);

namespace Expansa\Auth\Internal;

use Expansa\Auth\Exceptions\InvalidToken;
use JsonException;

/**
 * Signed JSON Web Token (RFC 7519) verification for OpenID Connect identity tokens: RS256 and
 * ES256 by a key of the issuer's JWK set. `none` and HMAC algorithms are rejected, so a token
 * can't choose a weaker check than the issuer's public key. Claims are checked by the caller.
 *
 * @internal
 */
final class Jwt
{
    /**
     * JWS algorithm names with their COSE IDs.
     */
    private const array ALGORITHMS = ['RS256' => PublicKey::RS256, 'ES256' => PublicKey::ES256];

    /**
     * Verify the signature and get the claims.
     *
     * @param string                           $token Compact serialization `header.payload.signature`.
     * @param array<int, array<string, mixed>> $keys  The `keys` of the issuer's JWK set.
     * @return array<string, mixed> Claims.
     * @throws InvalidToken If the token is malformed, signed by an unknown key or the signature is invalid.
     */
    public static function decode(string $token, array $keys): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidToken('Malformed token.');
        }

        $header    = self::json($parts[0]);
        $algorithm = self::ALGORITHMS[$header['alg'] ?? ''] ?? throw new InvalidToken('Unsupported token algorithm.');

        // a set may keep keys of several types and the old key during rotation
        $jwk = array_find($keys, fn (mixed $key) => is_array($key)
            && (! isset($header['kid']) || ($key['kid'] ?? null) === $header['kid'])
            && ($key['use'] ?? 'sig') === 'sig'
            && ($key['kty'] ?? null) === ($algorithm === PublicKey::RS256 ? 'RSA' : 'EC'));

        if (! is_array($jwk)) {
            throw new InvalidToken('The token is signed by an unknown key.');
        }

        [$keyAlgorithm, $pem] = PublicKey::fromJwk($jwk);

        $signature = self::bytes($parts[2]);
        if ($algorithm === PublicKey::ES256) {
            $signature = PublicKey::derSignature($signature);
        }

        $signed = $parts[0] . '.' . $parts[1];
        if ($keyAlgorithm !== $algorithm || $signature === '' || ! PublicKey::verify($algorithm, $pem, $signed, $signature)) {
            throw new InvalidToken('Invalid token signature.');
        }

        return self::json($parts[1]);
    }

    /**
     * Decode a base64url part.
     *
     * @param string $part
     * @return string
     * @throws InvalidToken If the part is not base64url.
     */
    private static function bytes(string $part): string
    {
        $bytes = base64_decode(strtr($part, '-_', '+/'), true);

        return $bytes === false ? throw new InvalidToken('Malformed token.') : $bytes;
    }

    /**
     * Decode a base64url JSON object part.
     *
     * @param string $part
     * @return array<string, mixed>
     * @throws InvalidToken If the part is not a JSON object.
     */
    private static function json(string $part): array
    {
        try {
            $data = json_decode(self::bytes($part), true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidToken('Malformed token.');
        }

        return is_array($data) ? $data : throw new InvalidToken('Malformed token.');
    }
}
