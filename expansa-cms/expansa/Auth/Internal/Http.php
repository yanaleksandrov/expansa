<?php

declare(strict_types=1);

namespace Expansa\Auth\Internal;

use Expansa\Auth\Exceptions\RequestFailed;

/**
 * Default transport of the providers: one HTTPS request through curl with verified certificates.
 * Replaced through `Auth::configure(transport:)`, e.g. by a fake in tests.
 *
 * @internal
 */
final class Http
{
    /**
     * Seconds to wait for the provider.
     */
    private const int TIMEOUT = 10;

    /**
     * Send a request.
     *
     * @param string                $method  `GET` or `POST`.
     * @param string                $url
     * @param array<string, string> $headers Header name => value.
     * @param string                $body    Request body for POST.
     * @return array{status: int, body: string}
     * @throws RequestFailed If curl is missing or the request fails before a response.
     */
    public static function send(string $method, string $url, array $headers = [], string $body = ''): array
    {
        if (! function_exists('curl_init')) {
            throw new RequestFailed('The curl extension is required to reach sign-in providers.');
        }

        $lines = array_map(fn (string $name, string $value) => "$name: $value", array_keys($headers), $headers);
        $curl  = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $lines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ] + ($method === 'POST' ? [CURLOPT_POSTFIELDS => $body] : []));

        $response = curl_exec($curl);
        if ($response === false) {
            throw new RequestFailed('The provider could not be reached: ' . curl_error($curl));
        }

        return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => (string) $response];
    }
}
