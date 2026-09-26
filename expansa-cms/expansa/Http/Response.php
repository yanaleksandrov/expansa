<?php

declare(strict_types=1);

namespace Expansa\Http;

use Stringable;

/**
 * Outgoing HTTP response: status, headers, cookies and body, sent with send().
 *
 * @package Expansa\Http
 */
final class Response
{
    /**
     * Charset appended to the Content-Type header.
     */
    private const string CHARSET = 'utf-8';

    /**
     * Set-Cookie header values.
     *
     * @var array<string|Stringable>
     */
    public private(set) array $cookies = [];

    public function __construct(

        /**
         * Response body.
         */
        public string $content = '',

        /**
         * HTTP status code.
         */
        public int $statusCode = 200,

        /**
         * Headers, name => value.
         *
         * @var array<string, string>
         */
        public array $headers = [],
    ) {}

    /**
     * Add a cookie, sent as a Set-Cookie header.
     *
     * @param string|Stringable $cookie The header value: `id=1; Path=/; HttpOnly` or an object that renders it.
     * @return static
     */
    public function setCookie(string|Stringable $cookie): static
    {
        $this->cookies[] = $cookie;

        return $this;
    }

    /**
     * Set the body to the JSON of the data along with the Content-Type header.
     *
     * @param array<array-key, mixed> $data
     * @param int                     $statusCode
     * @param array<string, string>   $headers    Added to the current headers.
     * @return static
     */
    public function json(array $data, int $statusCode = 200, array $headers = []): static
    {
        $this->content    = (string) json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->statusCode = $statusCode;
        $this->headers    = [...$this->headers, 'Content-Type' => 'application/json', ...$headers];

        return $this;
    }

    /**
     * Adjust the response to the request: a HEAD response has no body.
     *
     * @param Request $request
     * @return static
     */
    public function prepare(Request $request): static
    {
        if ($request->method === 'HEAD') {
            $this->content = '';
        }

        return $this;
    }

    /**
     * Send the headers and the body, then finish the request so the script can go on without the client waiting.
     *
     * @return static
     */
    public function send(): static
    {
        if (! headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                if (strcasecmp($name, 'Content-Type') === 0) {
                    $value .= '; charset=' . self::CHARSET;
                }
                header($name . ': ' . $value, false);
            }

            foreach ($this->cookies as $cookie) {
                header('Set-Cookie: ' . $cookie, false);
            }
        }

        echo $this->content;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            self::flushOutputBuffers();
        }

        return $this;
    }

    /**
     * Flush and close the output buffers that allow it.
     *
     * @return void
     */
    private static function flushOutputBuffers(): void
    {
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_FLUSHABLE;

        foreach (array_reverse(ob_get_status(true)) as $status) {
            if (($status['flags'] & $flags) !== $flags) {
                break;
            }
            ob_end_flush();
        }

        flush();
    }
}
