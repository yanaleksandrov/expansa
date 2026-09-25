<?php

declare(strict_types=1);

namespace Expansa\Codecs;

/**
 * Encodes binary data as standard or URL-safe Base64 (RFC 4648) and decodes it strictly.
 *
 * @package Expansa\Codecs
 */
class Base64
{
    /**
     * Encodes data as Base64.
     *
     * @param string $data
     * @param bool   $url  Use the URL-safe alphabet ("-" and "_") without "=" padding.
     * @return string
     */
    public function encode(string $data, bool $url = false): string
    {
        $encoded = base64_encode($data);

        return $url ? rtrim(strtr($encoded, '+/', '-_'), '=') : $encoded;
    }

    /**
     * Decodes standard or URL-safe Base64; returns null if the input contains other characters.
     * Missing padding and whitespace (e.g. MIME line breaks) are accepted.
     *
     * @param string $data
     * @return string|null
     */
    public function decode(string $data): ?string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
