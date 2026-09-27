<?php

declare(strict_types=1);

namespace Expansa\Http;

use Expansa\Http\Contracts\Request as RequestContract;
use Expansa\Http\Contracts\Response as ResponseContract;
use Stringable;

/**
 * Outgoing HTTP response: status, headers, cookies and body, sent with send().
 *
 * @package Expansa\Http
 */
final class Response implements ResponseContract
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
     * Forget the cookies added so far.
     *
     * @return void
     */
    public function flushCookies(): void
    {
        $this->cookies = [];
    }

    /**
     * Set a header, replacing the one with the same name; fluent alternative to $headers.
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

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
     * @param RequestContract $request
     * @return static
     */
    public function prepare(RequestContract $request): static
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
            self::closeOutputBuffers(0, flush: true);
        }

        return $this;
    }

    /**
     * Whether the status is a redirect (201 or 3xx with a location), to the location if given.
     *
     * @param string|null $location
     * @return bool
     */
    public function isRedirect(?string $location = null): bool
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308], true)
            && ($location === null || $location === ($this->headers['Location'] ?? null));
    }

    /**
     * Close the output buffers above the level that allow it, flushing or discarding their content.
     *
     * @param int  $targetLevel Buffer level to stop at, 0 closes all.
     * @param bool $flush       Send the content instead of discarding it.
     * @return void
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $flags  = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);
        $status = ob_get_status(true);

        for ($level = count($status) - 1; $level >= $targetLevel; $level--) {
            if (($status[$level]['flags'] & $flags) !== $flags) {
                break;
            }
            $flush ? ob_end_flush() : ob_end_clean();
        }

        if ($flush) {
            flush();
        }
    }
}
