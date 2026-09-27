<?php

declare(strict_types=1);

namespace Expansa\Cache\Traits;

/**
 * Encoding for backends that store only strings (file, table row, Redis, Memcached).
 * igbinary when loaded, smaller and faster than serialize(); the first byte marks the format,
 * so entries stay readable after the extension is added or removed.
 *
 * @package Expansa\Cache\Traits
 */
trait Serializes
{
    private const string FORMAT_NATIVE = "\x00";

    private const string FORMAT_IGBINARY = "\x01";

    private function serializeValue(mixed $value): string
    {
        if (extension_loaded('igbinary')) {
            return self::FORMAT_IGBINARY . igbinary_serialize($value);
        }

        return self::FORMAT_NATIVE . serialize($value);
    }

    /**
     * Decode a payload, null if its format extension is not loaded anymore.
     *
     * @param string $payload
     * @return mixed
     */
    private function unserializeValue(string $payload): mixed
    {
        $format = $payload[0] ?? self::FORMAT_NATIVE;
        $body   = substr($payload, 1);

        return match ($format) {
            self::FORMAT_IGBINARY => extension_loaded('igbinary') ? igbinary_unserialize($body) : null,
            default               => @unserialize($body),
        };
    }
}
