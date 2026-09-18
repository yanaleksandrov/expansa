<?php

declare(strict_types=1);

namespace Expansa\Cache\Concerns;

/**
 * Encodes values for backends that can only store strings (file, DB row, Redis, Memcached).
 * Prefers ext-igbinary when it's loaded — a binary format that's both smaller on the wire/disk
 * and faster to encode/decode than PHP's native serialize() for arrays and objects, which is
 * most of what ends up in this cache. Falls back to serialize() otherwise.
 *
 * Each payload is self-describing (a 1-byte format marker), so entries stay readable across a
 * deploy that adds or removes the igbinary extension instead of failing to decode.
 */
trait Serializes
{
    private const string FORMAT_NATIVE = "\x00";

    private const string FORMAT_IGBINARY = "\x01";

    /**
     * Encodes $value into a self-describing payload: a 1-byte format marker followed by the
     * encoded body, so unserializeValue() knows which decoder to use regardless of which one
     * was available when this was written.
     */
    private function serializeValue(mixed $value): string
    {
        if (extension_loaded('igbinary')) {
            return self::FORMAT_IGBINARY . igbinary_serialize($value);
        }

        return self::FORMAT_NATIVE . serialize($value);
    }

    /**
     * Returns null both for a payload this process can't decode (its encoder's extension was
     * removed since it was written) and for a value that was itself null — cache reads already
     * treat both as "nothing usable here" the same way.
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
