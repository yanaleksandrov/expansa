<?php

namespace Expansa\Http\Cookie;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use ReturnTypeWillChange;
use Expansa\Http\Cookie;
use Expansa\Http\HttpException;
use Expansa\Http\Contracts\HookManager;
use Expansa\Http\Iri;
use Expansa\Http\Response;

/**
 * Cookie holder object
 *
 * @package Expansa\Http
 */
class Jar implements ArrayAccess, IteratorAggregate
{
    /**
     * Create a new jar
     *
     * @param array $cookies Existing cookie values
     */
    public function __construct(
        protected array $cookies = []
    ) {} // phpcs:ignore

    /**
     * Normalise cookie data into a Cookie
     *
     * @param string|Cookie $cookie Cookie header value, possibly pre-parsed (object).
     * @param string        $key    Optional. The name for this cookie.
     * @return Cookie
     */
    public function normalize_cookie(string|Cookie $cookie, string $key = ''): Cookie
    {
        if ($cookie instanceof Cookie) {
            return $cookie;
        }
        return Cookie::parse($cookie, $key);
    }

    /**
     * Check if the given item exists
     *
     * @param string $offset Item key
     * @return bool Does the item exist?
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->cookies[$offset]);
    }

    /**
     * Get the value for the item
     *
     * @param string $offset Item key
     * @return string|null Item value (null if offsetExists is false)
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset): ?string
    {
        if (!isset($this->cookies[$offset])) {
            return null;
        }

        return $this->cookies[$offset];
    }

    /**
     * Set the given item
     *
     * @param string $offset Item name
     * @param string $value  Item value
     * @throws HttpException On attempting to use dictionary as list (`invalidset`).
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new HttpException('Object is a dictionary, not a list', 'invalidset');
        }

        $this->cookies[$offset] = $value;
    }

    /**
     * Unset the given header
     *
     * @param string $offset The key for the item to unset.
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->cookies[$offset]);
    }

    /**
     * Get an iterator for the data
     *
     * @return ArrayIterator
     */
    #[ReturnTypeWillChange]
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Register the cookie handler with the request's hooking system
     *
     * @param HookManager $hooks Hooking system
     */
    public function register(HookManager $hooks): void
    {
        $hooks->register('requests.before_request', [$this, 'before_request']);
        $hooks->register('requests.before_redirect_check', [$this, 'before_redirect_check']);
    }

    /**
     * Add Cookie header to a request if we have any
     * As per RFC 6265, cookies are separated by '; '
     *
     * @param string $url
     * @param array  $headers
     * @param array  $data
     * @param string $type
     * @param array  $options
     * @throws HttpException
     */
    public function before_request(string $url, array &$headers, array &$data, string &$type, array &$options): void
    {
        if (!$url instanceof Iri) {
            $url = new Iri($url);
        }

        if (!empty($this->cookies)) {
            $cookies = [];
            foreach ($this->cookies as $key => $cookie) {
                $cookie = $this->normalize_cookie($cookie, $key);

                // Skip expired cookies
                if ($cookie->is_expired()) {
                    continue;
                }

                if ($cookie->domain_matches($url->host)) {
                    $cookies[] = $cookie->format_for_header();
                }
            }

            $headers['Cookie'] = implode('; ', $cookies);
        }
    }

    /**
     * Parse all cookies from a response and attach them to the response
     *
     * @param Response $response Response as received.
     * @throws HttpException
     */
    public function before_redirect_check(Response $response): void
    {
        $url = $response->url;
        if (!$url instanceof Iri) {
            $url = new Iri($url);
        }

        $cookies           = Cookie::parse_from_headers($response->headers, $url);
        $this->cookies     = array_merge($this->cookies, $cookies);
        $response->cookies = $this;
    }
}
