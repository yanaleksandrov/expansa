<?php

declare(strict_types=1);

namespace Expansa\Webauthn\Internal;

use Expansa\Codecs\Base64;
use Expansa\Webauthn\Exceptions\InvalidCredential;

/**
 * Browser credential in the JSON shape of `PublicKeyCredential.toJSON()`: the credential ID
 * and base64url fields of its authenticator response.
 *
 * @internal
 */
final readonly class Payload
{
    /**
     * Longest credential ID the specification allows, in bytes.
     */
    private const int MAX_CREDENTIAL_ID = 1023;

    /**
     * Decoder of the binary fields.
     */
    private Base64 $base64;

    private function __construct(

        /**
         * Raw credential ID.
         */
        public string $id,

        /**
         * Authenticator response.
         *
         * @var array<string, mixed>
         */
        private array $response,
    ) {
        $this->base64 = new Base64();
    }

    /**
     * Decode the credential JSON.
     *
     * @param string $json
     * @return self
     * @throws InvalidCredential If it isn't a public key credential with a valid ID and a response.
     */
    public static function parse(string $json): self
    {
        $credential = json_decode($json, true, 16);

        if (
            ! is_array($credential)
            || ($credential['type'] ?? null) !== 'public-key'
            || ! is_string($credential['rawId'] ?? null)
            || ! is_array($credential['response'] ?? null)
        ) {
            throw new InvalidCredential('Malformed credential.');
        }

        $id = new Base64()->decode($credential['rawId']) ?? '';
        if ($id === '' || strlen($id) > self::MAX_CREDENTIAL_ID) {
            throw new InvalidCredential('Malformed credential ID.');
        }

        return new self($id, $credential['response']);
    }

    /**
     * Decode a required base64url field of the response.
     *
     * @param string $field
     * @return string
     * @throws InvalidCredential If the field is missing, empty or not base64url.
     */
    public function bytes(string $field): string
    {
        $value = $this->optional($field);

        return $value === null || $value === '' ? throw new InvalidCredential("Malformed \"$field\" field.") : $value;
    }

    /**
     * Decode an optional base64url field of the response.
     *
     * @param string $field
     * @return string|null Null for a missing or null field.
     * @throws InvalidCredential If the field is present but not base64url.
     */
    public function optional(string $field): ?string
    {
        $value = $this->response[$field] ?? null;
        if ($value === null) {
            return null;
        }

        $bytes = is_string($value) ? $this->base64->decode($value) : null;

        return $bytes ?? throw new InvalidCredential("Malformed \"$field\" field.");
    }

    /**
     * Strings of a list field of the response, other items are skipped.
     *
     * @param string $field
     * @return string[]
     */
    public function strings(string $field): array
    {
        $value = $this->response[$field] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
