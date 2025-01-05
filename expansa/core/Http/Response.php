<?php

namespace Expansa\Http;

use Expansa\Http\Cookie\Jar;
use Expansa\Http\Exception\Http;
use Expansa\Http\Response\Headers;

/**
 * HTTP response class
 * Contains a response from \Expansa\Http\Requests::request()
 *
 * @package Expansa\Http
 */
class Response
{
    /**
     * Response body
     *
     * @var string
     */
    public string $body = '';

    /**
     * Raw HTTP data from the transport
     *
     * @var string
     */
    public string $raw = '';

    /**
     * Headers, as an associative array
     *
     * @var Headers|array Array-like object representing headers
     */
    public Headers|array $headers = [];

    /**
     * Status code, false if non-blocking
     *
     * @var int|bool
     */
    public int|bool $status_code = false;

    /**
     * Protocol version, false if non-blocking
     *
     * @var float|bool
     */
    public float|bool $protocol_version = false;

    /**
     * Whether the request succeeded or not
     *
     * @var bool
     */
    public bool $success = false;

    /**
     * Number of redirects the request used
     *
     * @var int
     */
    public int $redirects = 0;

    /**
     * URL requested
     *
     * @var string
     */
    public string $url = '';

    /**
     * Previous requests (from redirects)
     *
     * @var array Array of \Expansa\Http\Response objects
     */
    public array $history = [];

    /**
     * Cookies from the request
     *
     * @var Jar|array Array-like object representing a cookie jar
     */
    public Jar|array $cookies = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->headers = new Headers();
        $this->cookies = new Jar();
    }

    /**
     * Is the response a redirect?
     *
     * @return bool True if redirect (3xx status), false if not.
     */
    public function is_redirect(): bool
    {
        $code = $this->status_code;
        return is_int($code) && (in_array($code, [300, 301, 302, 303, 307], true) || ($code > 307 && $code < 400));
    }

    /**
     * Throws an exception if the request was not successful
     *
     * @param bool $allow_redirects Set false to throw on a 3xx as well
     * @throws HttpException If `$allow_redirects` is false, and code is 3xx (`response.no_redirects`)
     * @throws \Expansa\Http\Exception\Http On non-successful status code. Exception class corresponds to "Status" + code (e.g. {@see \Expansa\Http\Exception\Http\Status404})
     */
    public function throw_for_status(bool $allow_redirects = true): void
    {
        if ($this->is_redirect()) {
            if ($allow_redirects !== true) {
                throw new HttpException('Redirection not allowed', 'response.no_redirects', $this);
            }
        } elseif (!$this->success) {
            $exception = Http::get_class($this->status_code);
            throw new $exception(null, $this);
        }
    }

    /**
     * JSON decode the response body.
     * The method parameters are the same as those for the PHP native `json_decode()` function.
     *
     * @link https://php.net/json-decode
     * @param bool|null $associative Optional. When `true`, JSON objects will be returned as associative arrays;
     *                               When `false`, JSON objects will be returned as objects.
     *                               When `null`, JSON objects will be returned as associative arrays
     *                               or objects depending on whether `JSON_OBJECT_AS_ARRAY` is set in the flags.
     *                               Defaults to `true` (in contrast to the PHP native default of `null`).
     * @param int       $depth       Optional. Maximum nesting depth of the structure being decoded.
     *                               Defaults to `512`.
     * @param int       $options     Optional. Bitmask of JSON_BIGINT_AS_STRING, JSON_INVALID_UTF8_IGNORE,
     *                               JSON_INVALID_UTF8_SUBSTITUTE, JSON_OBJECT_AS_ARRAY, JSON_THROW_ON_ERROR.
     *                               Defaults to `0` (no options set).
     * @return array
     * @throws HttpException If `$this->body` is not valid json.
     */
    public function decode_body(bool|null $associative = true, int $depth = 512, int $options = 0): array
    {
        $data = json_decode($this->body, $associative, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $last_error = json_last_error_msg();
            throw new HttpException('Unable to parse JSON data: ' . $last_error, 'response.invalid', $this);
        }

        return $data;
    }
}
