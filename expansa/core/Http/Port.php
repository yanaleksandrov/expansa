<?php

namespace Expansa\Http;

/**
 * Port utilities for Requests
 * Find the correct port depending on the Request type.
 *
 * @package Expansa\Http
 */
final class Port
{
    /**
     * Port to use with Acap requests.
     *
     * @var int
     */
    public const ACAP = 674;

    /**
     * Port to use with Dictionary requests.
     *
     * @var int
     */
    public const DICT = 2628;

    /**
     * Port to use with HTTP requests.
     *
     * @var int
     */
    public const HTTP = 80;

    /**
     * Port to use with HTTP over SSL requests.
     *
     * @var int
     */
    public const HTTPS = 443;

    /**
     * Retrieve the port number to use.
     *
     * @param string $type Request type. The following requests types are supported: 'acap', 'dict', 'http' and 'https'.
     * @return int
     * @throws HttpException       When a non-supported port is requested ('portnotsupported').
     */
    public static function get(string $type): int
    {
        $type = strtoupper($type);
        if (!defined("self::$type")) {
            throw new HttpException(sprintf('Invalid port type (%s) passed', $type), 'portnotsupported');
        }
        return constant("self::$type");
    }
}
