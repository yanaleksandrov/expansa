<?php

declare(strict_types=1);

namespace Expansa\Codecs;

/**
 * Encodes and decodes JSON. Errors never throw: encode() returns '' and decode() returns null.
 *
 * @package Expansa\Codecs
 */
class Json
{
    /**
     * Invalid UTF-8 is replaced with U+FFFD instead of failing the whole value.
     */
    private const int ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

    private const int DECODE_FLAGS = JSON_BIGINT_AS_STRING;

    /**
     * Converts a value to JSON; returns '' if the value cannot be encoded (e.g. NAN or a resource).
     *
     * @param mixed $value
     * @param bool  $ascii        Escape non-ASCII characters as \uXXXX.
     * @param bool  $pretty       Indent the output.
     * @param bool  $forceObjects Encode lists as objects.
     * @return string
     */
    public function encode(mixed $value, bool $ascii = false, bool $pretty = false, bool $forceObjects = false): string
    {
        $flags = self::ENCODE_FLAGS
            | ($ascii ? 0 : JSON_UNESCAPED_UNICODE)
            | ($pretty ? JSON_PRETTY_PRINT : 0)
            | ($forceObjects ? JSON_FORCE_OBJECT : 0);

        $json = json_encode($value, $flags);

        return $json === false ? '' : $json;
    }

    /**
     * Parses JSON into a PHP value; returns null on invalid JSON.
     * Integers beyond PHP_INT_MAX are returned as strings.
     *
     * @param string $json
     * @param bool   $forceArrays Decode objects as associative arrays.
     * @return mixed
     */
    public function decode(string $json, bool $forceArrays = false): mixed
    {
        // json_last_error() instead of JSON_THROW_ON_ERROR: exceptions double the cost of invalid input
        $value = json_decode($json, $forceArrays, 512, self::DECODE_FLAGS);

        return json_last_error() === JSON_ERROR_NONE ? $value : null;
    }

    /**
     * Checks that the value is a string with valid JSON.
     *
     * @param mixed $data
     * @return bool
     */
    public function isValid(mixed $data): bool
    {
        return is_string($data) && json_validate($data);
    }
}
