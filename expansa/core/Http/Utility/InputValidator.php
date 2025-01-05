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
    public static function is_numeric_array_key(mixed $input): bool
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
     * Verify whether a received input parameter is "stringable".
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     */
    public static function is_stringable_object(mixed $input): bool
    {
        return is_object($input) && method_exists($input, '__toString');
    }

    /**
     * Verify whether a received input parameter is _accessible as if it were an array_.
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     */
    public static function has_array_access(mixed $input): bool
    {
        return is_array($input) || $input instanceof ArrayAccess;
    }

    /**
     * Verify whether a received input parameter is a Curl handle.
     *
     * The PHP Curl extension worked with resources prior to PHP 8.0 and with
     * an instance of the `CurlHandle` class since PHP 8.0.
     * {@link https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.resource2object}
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     */
    public static function is_curl_handle(mixed $input): bool
    {
        if (is_resource($input)) {
            return get_resource_type($input) === 'curl';
        }

        if (is_object($input)) {
            return $input instanceof CurlHandle;
        }

        return false;
    }

    /**
     * Verify that a received input parameter is a valid "token name" according to the
     * specification in RFC 2616 (HTTP/1.1).
     *
     * The short version is: 1 or more ASCII characters, CTRL chars and separators not allowed.
     * For the long version, see the specs in the RFC.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc2616#section-2.2
     *
     * @param mixed $input Input parameter to verify.
     * @return bool
     */
    public static function is_valid_rfc2616_token(mixed $input): bool
    {
        if (!is_int($input) && !is_string($input)) {
            return false;
        }

        return preg_match('@^[0-9A-Za-z!#$%&\'*+.^_`|~-]+$@', $input) === 1;
    }
}
