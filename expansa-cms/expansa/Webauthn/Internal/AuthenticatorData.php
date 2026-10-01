<?php

declare(strict_types=1);

namespace Expansa\Webauthn\Internal;

use Expansa\Webauthn\Exceptions\InvalidCredential;

/**
 * Authenticator data: RP ID hash, flags, signature counter and, after registration,
 * the attested credential with its COSE public key; extensions are checked for form and skipped.
 *
 * @internal
 */
final readonly class AuthenticatorData
{
    /**
     * The user touched the authenticator.
     */
    public const int USER_PRESENT = 0x01;

    /**
     * The authenticator verified the user: biometrics, PIN or device password.
     */
    public const int USER_VERIFIED = 0x04;

    /**
     * The credential may be synced to other devices: a multi-device passkey.
     */
    public const int BACKUP_ELIGIBLE = 0x08;

    /**
     * The credential is synced right now.
     */
    public const int BACKED_UP = 0x10;

    /**
     * Attested credential data follows the counter.
     */
    public const int ATTESTED = 0x40;

    /**
     * An extensions map ends the data.
     */
    public const int EXTENSIONS = 0x80;

    private function __construct(

        /**
         * Data as the authenticator signed it.
         */
        public string $raw,

        /**
         * SHA-256 of the RP ID the authenticator scoped the credential to.
         */
        public string $rpIdHash,

        /**
         * Bit field of the flag constants.
         */
        public int $flags,

        /**
         * Signature counter, 0 for authenticators that don't keep one.
         */
        public int $counter,

        /**
         * Raw credential ID, only in registration data.
         */
        public ?string $credentialId = null,

        /**
         * COSE public key map, only in registration data.
         *
         * @var array<int, mixed>|null
         */
        public ?array $publicKey = null,
    ) {}

    /**
     * Parse raw authenticator data; every byte must belong to a part the flags announce.
     *
     * @param string $raw
     * @return self
     * @throws InvalidCredential If the data is truncated, malformed or has trailing bytes.
     */
    public static function parse(string $raw): self
    {
        $length = strlen($raw);
        if ($length < 37) {
            throw new InvalidCredential('Authenticator data is truncated.');
        }

        $flags        = ord($raw[32]);
        $counter      = unpack('N', $raw, 33)[1];
        $offset       = 37;
        $credentialId = null;
        $publicKey    = null;

        if ($flags & self::ATTESTED) {
            // 16-byte AAGUID, then a 2-byte credential ID length
            if ($length < 55) {
                throw new InvalidCredential('Attested credential data is truncated.');
            }

            $idLength     = unpack('n', $raw, 53)[1];
            $credentialId = substr($raw, 55, $idLength);
            $publicKey    = Cbor::decodeFirst(substr($raw, 55 + $idLength), $keyLength);

            if (strlen($credentialId) !== $idLength || ! is_array($publicKey)) {
                throw new InvalidCredential('Attested credential data is malformed.');
            }

            $offset = 55 + $idLength + $keyLength;
        }

        if ($flags & self::EXTENSIONS) {
            if (! is_array(Cbor::decodeFirst(substr($raw, $offset), $extensionsLength))) {
                throw new InvalidCredential('Authenticator extensions are malformed.');
            }

            $offset += $extensionsLength;
        }

        if ($offset !== $length) {
            throw new InvalidCredential('Authenticator data has trailing bytes.');
        }

        return new self($raw, substr($raw, 0, 32), $flags, $counter, $credentialId, $publicKey);
    }

    /**
     * Whether all given flags are set.
     *
     * @param int $flags Flag constants combined with `|`.
     * @return bool
     */
    public function has(int $flags): bool
    {
        return ($this->flags & $flags) === $flags;
    }
}
