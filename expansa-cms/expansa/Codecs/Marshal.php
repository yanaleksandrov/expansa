<?php

declare(strict_types=1);

namespace Expansa\Codecs;

use Throwable;

/**
 * Native PHP serialize() format with safe decoding: objects are not restored unless explicitly allowed.
 * Named after Ruby's Marshal, the same kind of format. Errors never throw: encode() returns '', decode() null.
 *
 * @package Expansa\Codecs
 */
class Marshal
{
    /**
     * Optional 1-byte format markers; a plain serialize() string never starts with either of them.
     * igbinary payloads always carry one, so they stay readable if the extension is added or removed.
     */
    private const string FORMAT_NATIVE = "\x00";

    private const string FORMAT_IGBINARY = "\x01";

    /**
     * Serializes a value; returns '' if it cannot be serialized (e.g. a closure).
     * igbinary output is smaller and faster but can be decoded only from a trusted source, see decode().
     *
     * @param mixed $value
     * @param bool  $igbinary Use ext-igbinary when it is loaded, otherwise falls back to serialize().
     * @return string
     */
    public function encode(mixed $value, bool $igbinary = false): string
    {
        try {
            return $igbinary && extension_loaded('igbinary')
                ? self::FORMAT_IGBINARY . igbinary_serialize($value)
                : serialize($value);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Restores a value from encode() output or any serialize() string, with or without a format marker.
     * Returns null if the payload is invalid.
     * By default objects become __PHP_Incomplete_Class, so untrusted data cannot run their magic methods.
     *
     * @param string        $payload
     * @param bool|string[] $allowedClasses True for any class, or a list of class names to restore.
     * @return mixed
     */
    public function decode(string $payload, bool|array $allowedClasses = false): mixed
    {
        try {
            return match ($payload[0] ?? '') {
                ''                    => null,
                self::FORMAT_IGBINARY => $this->decodeIgbinary(substr($payload, 1), $allowedClasses),
                self::FORMAT_NATIVE   => $this->decodeNative(substr($payload, 1), $allowedClasses),
                default               => $this->decodeNative($payload, $allowedClasses),
            };
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Decodes a serialize() string, telling a serialized false apart from a failure.
     *
     * @param string        $body
     * @param bool|string[] $allowedClasses
     * @return mixed
     */
    private function decodeNative(string $body, bool|array $allowedClasses): mixed
    {
        // a handler instead of @: custom error handlers still receive @-silenced warnings
        set_error_handler(static fn (): bool => true);
        try {
            $value = unserialize($body, ['allowed_classes' => $allowedClasses]);
        } finally {
            restore_error_handler();
        }

        return $value === false && $body !== 'b:0;' ? null : $value;
    }

    /**
     * Decodes an igbinary body only for fully trusted input.
     *
     * @param string        $body
     * @param bool|string[] $allowedClasses
     * @return mixed
     */
    private function decodeIgbinary(string $body, bool|array $allowedClasses): mixed
    {
        // igbinary cannot limit classes: objects are woken up before any check could reject them
        if ($allowedClasses !== true || ! extension_loaded('igbinary')) {
            return null;
        }

        return igbinary_unserialize($body);
    }
}
