<?php

namespace Expansa\Http\Contracts;

/**
 * Base HTTP transport
 *
 * @package Expansa\Http
 */
interface Transport
{
    /**
     * Perform a request
     *
     * @param string       $url     URL to request
     * @param array        $headers Associative array of request headers
     * @param string|array $data    Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
     * @param array        $options Request options, see {@see Requests::response()} for documentation
     * @return string Raw HTTP result
     */
    public function request(string $url, array $headers = [], string|array $data = [], array $options = []): string;

    /**
     * Send multiple requests simultaneously
     *
     * @param array $requests Request data (array of 'url', 'headers', 'data', 'options') as per {@see Transport::request()}
     * @param array $options  Global options, see {@see Requests::response()} for documentation
     * @return array Array of Response objects (may contain Exception or string responses as well)
     */
    public function requestMultiple(array $requests, array $options): array;

    /**
     * Self-test whether the transport can be used.
     *
     * The available capabilities to test for can be found in {@see Capability}.
     *
     * @param array<string, bool> $capabilities Associative array of capabilities to test against, i.e. `['<capability>' => true]`.
     * @return bool Whether the transport can be used.
     */
    public static function test(array $capabilities = []): bool;
}
