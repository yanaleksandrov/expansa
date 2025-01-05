<?php

namespace Expansa\Http;

use Stringable;
use Expansa\Http\Cookie\Jar;
use Expansa\Http\Exception\InvalidArgument;
use Expansa\Http\Iri;
use Expansa\Http\Requests;
use Expansa\Http\Utility\InputValidator;

/**
 * Session handler for persistent requests and default parameters
 *
 * Allows various options to be set as default values, and merges both the
 * options and URL properties together. A base URL can be set for all requests,
 * with all subrequests resolved from this. Base options can be set (including
 * a shared cookie jar), then overridden for individual requests.
 *
 * @package Requests\SessionHandler
 */
class Session
{
    /**
     * Create a new session
     *
     * @param string|Stringable|null  $url     Base URL for requests, URLs will be made absolute using this as the base
     * @param array                   $headers Default headers for requests
     * @param array                   $data    Default data for requests. If both the base data and the per-request
     *                                         data are arrays, the data will be merged before sending the request.
     * @param array                   $options Default options for requests. The base options are merged with the per-request data for each request.
     *                                         The only default option is a shared cookie jar between requests.
     *                                         Values here can also be set directly via properties on the Session
     *                                         object, e.g. `$session->useragent = 'X';`
     * @throws InvalidArgument When the passed $url argument is not a string, Stringable or null.
     * @throws InvalidArgument When the passed $headers argument is not an array.
     * @throws InvalidArgument When the passed $data argument is not an array.
     * @throws InvalidArgument When the passed $options argument is not an array.
     */
    public function __construct(
        public null|string|Stringable $url = null,
        public array $headers = [],
        public array $data = [],
        public array $options = []
    )
    {
        if ($url !== null && InputValidator::is_string_or_stringable($url) === false) {
            throw InvalidArgument::create(1, '$url', 'string|Stringable|null', gettype($url));
        }

        if (is_array($headers) === false) {
            throw InvalidArgument::create(2, '$headers', 'array', gettype($headers));
        }

        if (is_array($data) === false) {
            throw InvalidArgument::create(3, '$data', 'array', gettype($data));
        }

        if (is_array($options) === false) {
            throw InvalidArgument::create(4, '$options', 'array', gettype($options));
        }

        if (empty($this->options['cookies'])) {
            $this->options['cookies'] = new Jar();
        }
    }

    /**
     * Get a property's value
     *
     * @param string $name Property name.
     * @return mixed|null Property value, null if none found
     */
    public function __get(string $name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return null;
    }

    /**
     * Set a property's value
     *
     * @param string $name  Property name.
     * @param mixed  $value Property value
     */
    public function __set(string $name, mixed $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Remove a property's value
     *
     * @param string $name Property name.
     */
    public function __isset(string $name)
    {
        return isset($this->options[$name]);
    }

    /**
     * Remove a property's value
     *
     * @param string $name Property name.
     */
    public function __unset(string $name)
    {
        unset($this->options[$name]);
    }

    /**
     * Send a GET request
     *
     * @param string $url
     * @param array  $headers
     * @param array  $options
     * @return Response
     * @throws Exception
     */
    public function get(string $url, array $headers = [], array $options = []): Response
    {
        return $this->request($url, $headers, null, Requests::GET, $options);
    }

    /**
     * Send a HEAD request
     *
     * @throws Exception
     */
    public function head($url, $headers = [], $options = []): Response
    {
        return $this->request($url, $headers, null, Requests::HEAD, $options);
    }

    /**
     * Send a DELETE request
     *
     * @throws Exception
     */
    public function delete($url, $headers = [], $options = []): Response
    {
        return $this->request($url, $headers, null, Requests::DELETE, $options);
    }

    /**
     * Send a POST request
     *
     * @param string $url
     * @param array  $headers
     * @param array  $data
     * @param array  $options
     * @return Response
     * @throws Exception
     */
    public function post(string $url, array $headers = [], array $data = [], array $options = []): Response
    {
        return $this->request($url, $headers, $data, Requests::POST, $options);
    }

    /**
     * Send a PUT request
     *
     * @throws Exception
     */
    public function put($url, $headers = [], $data = [], $options = []): Response
    {
        return $this->request($url, $headers, $data, Requests::PUT, $options);
    }

    /**
     * Send a PATCH request
     * Note: Unlike {@see \Expansa\Http\Session::post()} and {@see \Expansa\Http\Session::put()},
     * `$headers` is required, as the specification recommends that should send an ETag
     *
     * @link https://tools.ietf.org/html/rfc5789
     * @throws Exception
     */
    public function patch($url, $headers, $data = [], $options = []): Response
    {
        return $this->request($url, $headers, $data, Requests::PATCH, $options);
    }

    /**
     * Main interface for HTTP requests
     * This method initiates a request and sends it via transport before parsing.
     *
     * @param string     $url     URL to request
     * @param array      $headers Extra headers to send with the request
     * @param array|null $data    Data to send as a query string for GET/HEAD requests or in the body for POST requests.
     * @param string     $type    HTTP request type (use \Expansa\Http\Requests constants)
     * @param array      $options Options for the request (see {@see \Expansa\Http\Requests::request()})
     * @return Response
     * @throws Exception On invalid URLs (`nonhttp`)
     */
    public function request(
        string $url,
        array $headers = [],
        ?array $data = [],
        string $type = Requests::GET,
        array $options = []
    ): Response
    {
        $request = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $data,
            'options' => $options,
        ];
        $request = $this->merge_request($request);

        return Requests::request($request['url'], $request['headers'], $request['data'], $type, $request['options']);
    }

    /**
     * Send multiple HTTP requests simultaneously.
     *
     * @param array $requests Requests data.
     * @param array $options  Global and default options (see {@see \Expansa\Http\Requests::request()})
     * @return array Responses (either Response or a Exception object)
     *
     * @throws InvalidArgument When the passed $requests argument is not an array or iterable object with array access.
     * @throws InvalidArgument When the passed $options argument is not an array.
     */
    public function request_multiple(array $requests, array $options = []): array
    {
        if (InputValidator::has_array_access($requests) === false || InputValidator::is_iterable($requests) === false) {
            throw InvalidArgument::create(1, '$requests', 'array|ArrayAccess&Traversable', gettype($requests));
        }

        foreach ($requests as $key => $request) {
            $requests[$key] = $this->merge_request($request, false);
        }

        $options = array_merge($this->options, $options);

        // Disallow forcing the type, as that's a per-request setting
        unset($options['type']);

        return Requests::request_multiple($requests, $options);
    }

    /**
     * Merge a request's data with the default data
     *
     * @param array $request       Request data (same form as {@see \Expansa\Http\Session::request_multiple()})
     * @param bool  $merge_options Should we merge options as well?
     * @return array Request data
     */
    protected function merge_request(array $request, bool $merge_options = true): array
    {
        if ($this->url !== null) {
            $request['url'] = Iri::absolutize($this->url, $request['url']);
            $request['url'] = $request['url']->uri;
        }

        if (empty($request['headers'])) {
            $request['headers'] = [];
        }

        $request['headers'] = array_merge($this->headers, $request['headers']);

        if (empty($request['data'])) {
            if (is_array($this->data)) {
                $request['data'] = $this->data;
            }
        } elseif (is_array($request['data']) && is_array($this->data)) {
            $request['data'] = array_merge($this->data, $request['data']);
        }

        if ($merge_options === true) {
            $request['options'] = array_merge($this->options, $request['options']);

            // Disallow forcing the type, as that's a per-request setting
            unset($request['options']['type']);
        }

        return $request;
    }
}
