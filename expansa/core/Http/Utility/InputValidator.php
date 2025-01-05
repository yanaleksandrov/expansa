<?php

namespace Expansa\Http\Utility;

use ArrayAccess;
use CurlHandle;

/**
 * Input validation utilities.
 *
 * @package Expansa\Http
 */
final class InputValidator
{
    /**
     * Verify whether a received input parameter is usable as an integer array key.
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     */
    public static function isNumericArrayKey(mixed $input): bool
    {
        if (is_int($input)) {
            return true;
        }

        if (!is_string($input)) {
            return false;
        }

        return (bool) preg_match('`^-?[0-9]+$`', $input);
    }

    /**
     * Verify whether a received input parameter is _accessible as if it were an array_.
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     */
    public static function hasArrayAccess(mixed $input): bool
    {
        return is_array($input) || $input instanceof ArrayAccess;
    }

    /**
     * Verify whether a received input parameter is a Curl handle.
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     */
    public static function isCurl(mixed $input): bool
    {
        return $input instanceof CurlHandle;
    }

    /**
     * Verify that a received input parameter is a valid "token name" according to the specification in RFC 2616
     * (HTTP/1.1). The short version is: 1 or more ASCII characters, CTRL chars and separators not allowed.
     * For the long version, see the specs in the RFC.
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     * @link https://datatracker.ietf.org/doc/html/rfc2616#section-2.2
     */
    public static function isValidRfc2616Token(mixed $input): bool
    {
        if (!is_int($input) && !is_string($input)) {
            return false;
        }
        return preg_match('@^[0-9A-Za-z!#$%&\'*+.^_`|~-]+$@', $input) === 1;
    }
}
