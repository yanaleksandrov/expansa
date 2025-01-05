<?php

namespace Expansa\Http;

use Stringable;
use Expansa\Http\Auth\Basic;
use Expansa\Http\Contracts\Capability;
use Expansa\Http\Contracts\Transport;
use Expansa\Http\Cookie\Jar;
use Expansa\Http\Exception\InvalidArgument;
use Expansa\Http\Proxy\Http;
use Expansa\Http\Transport\Curl;
use Expansa\Http\Transport\Fsockopen;
use Expansa\Http\Utility\InputValidator;

/**
 * Requests for PHP
 *
 * @package Expansa\Http
 */
class Requests
{
    /**
     * POST method
     *
     * @var string
     */
    public const POST = 'POST';

    /**
     * PUT method
     *
     * @var string
     */
    public const PUT = 'PUT';

    /**
     * GET method
     *
     * @var string
     */
    public const GET = 'GET';

    /**
     * HEAD method
     *
     * @var string
     */
    public const HEAD = 'HEAD';

    /**
     * DELETE method
     *
     * @var string
     */
    public const DELETE = 'DELETE';

    /**
     * OPTIONS method
     *
     * @var string
     */
    public const OPTIONS = 'OPTIONS';

    /**
     * TRACE method
     *
     * @var string
     */
    public const TRACE = 'TRACE';

    /**
     * PATCH method
     *
     * @link https://tools.ietf.org/html/rfc5789
     * @var string
     */
    public const PATCH = 'PATCH';

    /**
     * Default size of buffer size to read streams
     *
     * @var int
     */
    public const BUFFER_SIZE = 1160;

    /**
     * Option defaults.
     *
     * @see Requests::getDefaultOptions()
     * @see Requests::request() for values returned by this method
     *
     * @var array
     */
    public const OPTION_DEFAULTS = [
        'timeout'          => 10,
        'connect_timeout'  => 10,
        'useragent'        => 'php-requests/' . self::VERSION,
        'protocol_version' => 1.1,
        'redirected'       => 0,
        'redirects'        => 10,
        'follow_redirects' => true,
        'blocking'         => true,
        'type'             => self::GET,
        'filename'         => false,
        'auth'             => false,
        'proxy'            => false,
        'cookies'          => false,
        'max_bytes'        => false,
        'idn'              => true,
        'hooks'            => null,
        'transport'        => null,
        'verify'           => null,
        'verifyname'       => true,
    ];

    /**
     * Default supported Transport classes.
     *
     * @var array
     */
    public const DEFAULT_TRANSPORTS = [
        Curl::class      => Curl::class,
        Fsockopen::class => Fsockopen::class,
    ];

    /**
     * Current version of Requests
     *
     * @var string
     */
    public const VERSION = '2.0.14';

    /**
     * Selected transport name
     *
     * Use {@see Requests::getTransport()} instead
     *
     * @var array
     */
    public static array $transport = [];

    /**
     * Registered transport classes
     *
     * @var array
     */
    protected static array $transports = [];

    /**
     * Default certificate path.
     *
     * @see Requests::getCertificatePath()
     * @see Requests::setCertificatePath()
     *
     * @var string|Stringable|bool
     */
    protected static string|Stringable|bool $certificate_path = __DIR__ . '/../certificates/cacert.pem';

    /**
     * All (known) valid deflate, gzip header magic markers.
     *
     * These markers relate to different compression levels.
     *
     * @link https://stackoverflow.com/a/43170354/482864 Marker source.
     *
     * @var array
     */
    private static array $magic_compression_headers = [
        "\x1f\x8b" => true, // Gzip marker.
        "\x78\x01" => true, // Zlib marker - level 1.
        "\x78\x5e" => true, // Zlib marker - level 2 to 5.
        "\x78\x9c" => true, // Zlib marker - level 6.
        "\x78\xda" => true, // Zlib marker - level 7 to 9.
    ];

    /**
     * This is a static class, do not instantiate it
     *
     * @codeCoverageIgnore
     */
    private function __construct() {} // phpcs:ignore

    /**
     * Register a transport
     *
     * @param string $transport Transport class to add, must support the Transport interface
     */
    public static function addTransport(string $transport): void
    {
        if (empty(self::$transports)) {
            self::$transports = self::DEFAULT_TRANSPORTS;
        }

        self::$transports[$transport] = $transport;
    }

    /**
     * Get the fully qualified class name (FQCN) for a working transport.
     *
     * @param array<string, bool> $capabilities Optional. Associative array of capabilities to test against, i.e. `['<capability>' => true]`.
     * @return string FQCN of the transport to use, or an empty string if no transport was
     *                found which provided the requested capabilities.
     */
    protected static function getTransportClass(array $capabilities = []): string
    {
        // Caching code, don't bother testing coverage.
        // @codeCoverageIgnoreStart
        // Array of capabilities as a string to be used as an array key.
        ksort($capabilities);
        $cap_string = serialize($capabilities);

        // Don't search for a transport if it's already been done for these $capabilities.
        if (isset(self::$transport[$cap_string])) {
            return self::$transport[$cap_string];
        }

        // Ensure we will not run this same check again later on.
        self::$transport[$cap_string] = '';
        // @codeCoverageIgnoreEnd

        if (empty(self::$transports)) {
            self::$transports = self::DEFAULT_TRANSPORTS;
        }

        // Find us a working transport.
        foreach (self::$transports as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $result = $class::test($capabilities);
            if ($result === true) {
                self::$transport[$cap_string] = $class;
                break;
            }
        }

        return self::$transport[$cap_string];
    }

    /**
     * Get a working transport.
     *
     * @param array<string, bool> $capabilities Optional. Associative array of capabilities to test against, i.e. `['<capability>' => true]`.
     * @return Transport
     * @throws HttpException If no valid transport is found (`notransport`).
     */
    protected static function getTransport(array $capabilities = []): Transport
    {
        $class = self::getTransportClass($capabilities);

        if ($class === '') {
            throw new HttpException('No working transports found', 'notransport', self::$transports);
        }

        return new $class();
    }

    /**
     * Checks to see if we have transport for the capabilities requested.
     * Supported capabilities can be found in the {@see Capability}
     * interface as constants.
     * Example usage:
     * `Requests::hasCapabilities([Capability::SSL => true])`.
     *
     * @param array<string, bool> $capabilities Optional. Associative array of capabilities to test against, i.e. `['<capability>' => true]`.
     * @return bool Whether the transport has the requested capabilities.
     */
    public static function hasCapabilities(array $capabilities = []): bool
    {
        return self::getTransportClass($capabilities) !== '';
    }

    /**
     *  Send a GET request
     *
     * @param string $url
     * @param array  $headers
     * @param array  $options
     * @return Response
     * @throws HttpException
     */
    public static function get(string $url, array $headers = [], array $options = []): Response
    {
        return self::request($url, $headers, null, self::GET, $options);
    }

    /**
     * Send a HEAD request
     *
     * @throws HttpException
     */
    public static function head($url, $headers = [], $options = []): Response
    {
        return self::request($url, $headers, null, self::HEAD, $options);
    }

    /**
     * Send a DELETE request
     *
     * @throws HttpException
     */
    public static function delete($url, $headers = [], $options = []): Response
    {
        return self::request($url, $headers, null, self::DELETE, $options);
    }

    /**
     * Send a TRACE request
     *
     * @throws HttpException
     */
    public static function trace($url, $headers = [], $options = []): Response
    {
        return self::request($url, $headers, null, self::TRACE, $options);
    }

    /**
     * Send a POST request
     *
     * @param string $url
     * @param array  $headers
     * @param array  $data
     * @param array  $options
     * @return Response
     * @throws HttpException
     */
    public static function post(string $url, array $headers = [], array $data = [], array $options = []): Response
    {
        return self::request($url, $headers, $data, self::POST, $options);
    }

    /**
     * Send a PUT request
     *
     * @throws HttpException
     */
    public static function put($url, $headers = [], $data = [], $options = []): Response
    {
        return self::request($url, $headers, $data, self::PUT, $options);
    }

    /**
     * Send an OPTIONS request
     *
     * @throws HttpException
     */
    public static function options($url, $headers = [], $data = [], $options = []): Response
    {
        return self::request($url, $headers, $data, self::OPTIONS, $options);
    }

    /**
     * Send a PATCH request
     * Note: Unlike {@see Requests::post()} and {@see Requests::put()},
     * `$headers` is required, as the specification recommends that should send an ETag
     *
     * @link https://tools.ietf.org/html/rfc5789
     * @throws HttpException
     */
    public static function patch($url, $headers, $data = [], $options = []): Response
    {
        return self::request($url, $headers, $data, self::PATCH, $options);
    }

    /**
     * Main interface for HTTP requests
     * This method initiates a request and sends it via transport before parsing.
     * The `$options` parameter takes an associative array with the following
     * options:
     * - `timeout`: How long should we wait for a response?
     *    Note: for cURL, a minimum of 1 second applies, as DNS resolution
     *    operates at second-resolution only.
     *    (float, seconds with a millisecond precision, default: 10, example: 0.01)
     * - `connect_timeout`: How long should we wait while trying to connect?
     *    (float, seconds with a millisecond precision, default: 10, example: 0.01)
     * - `useragent`: Useragent to send to the server
     *    (string, default: php-requests/$version)
     * - `follow_redirects`: Should we follow 3xx redirects?
     *    (bool, default: true)
     * - `redirects`: How many times should we redirect before erroring?
     *    (int, default: 10)
     * - `blocking`: Should we block processing on this request?
     *    (bool, default: true)
     * - `filename`: File to stream the body to instead.
     *    (string|bool, default: false)
     * - `auth`: Authentication handler or array of user/password details to use
     *    for Basic authentication
     *    (\Expansa\Http\Auth|array|bool, default: false)
     * - `proxy`: Proxy details to use for proxy by-passing and authentication
     *    (\Expansa\Http\Proxy|array|string|bool, default: false)
     * - `max_bytes`: Limit for the response body size.
     *    (int|bool, default: false)
     * - `idn`: Enable IDN parsing
     *    (bool, default: true)
     * - `transport`: Custom transport. Either a class name, or a
     *    transport object. Defaults to the first working transport from
     *    {@see Requests::getTransport()}
     *    (string|\Expansa\Http\Transport, default: {@see Requests::getTransport()})
     * - `hooks`: Hooks handler.
     *    (\Expansa\Http\HookManager, default: new Expansa\Http\Hooks())
     * - `verify`: Should we verify SSL certificates? Allows passing in a custom
     *    certificate file as a string. (Using true uses the system-wide root
     *    certificate store instead, but this may have different behaviour
     *    across transports.)
     *    (string|bool, default: certificates/cacert.pem)
     * - `verifyname`: Should we verify the common name in the SSL certificate?
     *    (bool, default: true)
     * - `data_format`: How should we send the `$data` parameter?
     *    (string, one of 'query' or 'body', default: 'query' for
     *    HEAD/GET/DELETE, 'body' for POST/PUT/OPTIONS/PATCH)
     *
     * @param string|Stringable $url     URL to request
     * @param array              $headers Extra headers to send with the request
     * @param array|null         $data    Data to send either as a query string for GET/HEAD requests, or in the body for POST requests
     * @param string             $type    HTTP request type (use Requests constants)
     * @param array              $options Options for the request (see description for more information)
     * @return Response
     * @throws HttpException On invalid URLs (`nonhttp`)
     */
    public static function request(
        string|Stringable $url,
        array $headers = [],
        ?array $data = [],
        string $type = self::GET,
        array $options = []
    ): Response
    {
        if (empty($options['type'])) {
            $options['type'] = $type;
        }

        $options = array_merge(self::getDefaultOptions(), $options);

        self::setDefaults($url, $headers, $data, $type, $options);

        $options['hooks']->dispatch('requests.before_request', [&$url, &$headers, &$data, &$type, &$options]);

        if (!empty($options['transport'])) {
            $transport = $options['transport'];

            if (is_string($options['transport'])) {
                $transport = new $transport();
            }
        } else {
            $need_ssl     = (stripos($url, 'https://') === 0);
            $capabilities = [Capability::SSL => $need_ssl];
            $transport    = self::getTransport($capabilities);
        }

        $response = $transport->request($url, $headers, $data, $options);

        $options['hooks']->dispatch('requests.before_parse', [&$response, $url, $headers, $data, $type, $options]);

        return self::parse_response($response, $url, $headers, $data, $options);
    }

    /**
     * Send multiple HTTP requests simultaneously
     * The `$requests` parameter takes an associative or indexed array of
     * request fields. The key of each request can be used to match up the
     * request with the returned data, or with the request passed into your
     * `multiple.request.complete` callback.
     * The request fields value is an associative array with the following keys:
     * - `url`: Request URL Same as the `$url` parameter to
     *    {@see Requests::request()}
     *    (string, required)
     * - `headers`: Associative array of header fields. Same as the `$headers`
     *    parameter to {@see Requests::request()}
     *    (array, default: `array()`)
     * - `data`: Associative array of data fields or a string. Same as the
     *    `$data` parameter to {@see Requests::request()}
     *    (array|string, default: `array()`)
     * - `type`: HTTP request type (use Requests constants). Same as the `$type`
     *    parameter to {@see Requests::request()}
     *    (string, default: `Requests::GET`)
     * - `cookies`: Associative array of cookie name to value, or cookie jar.
     *    (array|\Expansa\Http\Cookie\Jar)
     * If the `$options` parameter is specified, individual requests will
     * inherit options from it. This can be used to use a single hooking system,
     * or set all the types to `Requests::POST`, for example.
     * In addition, the `$options` parameter takes the following global options:
     * - `complete`: A callback for when a request is complete. Takes two
     *    parameters, a Response/Exception reference, and the
     *    ID from the request array (Note: this can also be overridden on a
     *    per-request basis, although that's a little silly)
     *    (callback)
     *
     * @param array $requests Requests data (see description for more information)
     * @param array $options  Global and default options (see {@see Requests::request()})
     * @return array Responses (either Response or a Exception object)
     * @throws InvalidArgument When the passed $requests argument is not an array or iterable object with array access.
     */
    public static function requestMultiple(array $requests, array $options = []): array
    {
        if (InputValidator::hasArrayAccess($requests) === false || is_iterable($requests) === false) {
            throw InvalidArgument::create(1, '$requests', 'array|ArrayAccess&Traversable', gettype($requests));
        }

        $options = array_merge(self::getDefaultOptions(true), $options);

        if (!empty($options['hooks'])) {
            $options['hooks']->register('transport.internal.parse_response', [static::class, 'parseMultiple']);
            if (!empty($options['complete'])) {
                $options['hooks']->register('multiple.request.complete', $options['complete']);
            }
        }

        foreach ($requests as $id => &$request) {
            if (!isset($request['headers'])) {
                $request['headers'] = [];
            }

            if (!isset($request['data'])) {
                $request['data'] = [];
            }

            if (!isset($request['type'])) {
                $request['type'] = self::GET;
            }

            if (!isset($request['options'])) {
                $request['options']         = $options;
                $request['options']['type'] = $request['type'];
            } else {
                if (empty($request['options']['type'])) {
                    $request['options']['type'] = $request['type'];
                }

                $request['options'] = array_merge($options, $request['options']);
            }

            self::setDefaults($request['url'], $request['headers'], $request['data'], $request['type'], $request['options']);

            // Ensure we only hook in once
            if ($request['options']['hooks'] !== $options['hooks']) {
                $request['options']['hooks']->register('transport.internal.parse_response', [static::class, 'parseMultiple']);
                if (!empty($request['options']['complete'])) {
                    $request['options']['hooks']->register('multiple.request.complete', $request['options']['complete']);
                }
            }
        }

        unset($request);

        if (!empty($options['transport'])) {
            $transport = $options['transport'];

            if (is_string($options['transport'])) {
                $transport = new $transport();
            }
        } else {
            $transport = self::getTransport();
        }

        $responses = $transport->requestMultiple($requests, $options);

        foreach ($responses as $id => &$response) {
            // If our hook got messed with somehow, ensure we end up with the correct response
            if (is_string($response)) {
                $request = $requests[$id];
                self::parseMultiple($response, $request);
                $request['options']['hooks']->dispatch('multiple.request.complete', [&$response, $id]);
            }
        }

        return $responses;
    }

    /**
     * Get the default options
     *
     * @see Requests::request() for values returned by this method
     * @param bool $multirequest Is this a multirequest?
     * @return array Default option values
     */
    protected static function getDefaultOptions(bool $multirequest = false): array
    {
        $defaults           = static::OPTION_DEFAULTS;
        $defaults['verify'] = self::$certificate_path;

        if ($multirequest !== false) {
            $defaults['complete'] = null;
        }

        return $defaults;
    }

    /**
     * Get default certificate path.
     *
     * @return string Default certificate path.
     */
    public static function getCertificatePath(): string
    {
        return self::$certificate_path;
    }

    /**
     * Set default certificate path.
     *
     * @param string|Stringable|bool $path Certificate path, pointing to a PEM file.
     */
    public static function setCertificatePath(string|Stringable|bool $path): void
    {
        self::$certificate_path = $path;
    }

    /**
     * Set the default values
     * The $options parameter is updated with the results.
     *
     * @param string     $url     URL to request
     * @param array      $headers Extra headers to send with the request
     * @param array|null $data    Data to send either as a query string for GET/HEAD requests, or in the body for POST requests
     * @param string     $type    HTTP request type
     * @param array      $options Options for the request
     * @return void
     * @throws HttpException When the $url is not an http(s) URL.
     */
    protected static function setDefaults(&$url, &$headers, &$data, &$type, &$options): void
    {
        if (!preg_match('/^http(s)?:\/\//i', $url, $matches)) {
            throw new HttpException('Only HTTP(S) requests are handled.', 'nonhttp', $url);
        }

        if (empty($options['hooks'])) {
            $options['hooks'] = new Hooks();
        }

        if (is_array($options['auth'])) {
            $options['auth'] = new Basic($options['auth']);
        }

        if ($options['auth'] !== false) {
            $options['auth']->register($options['hooks']);
        }

        if (is_string($options['proxy']) || is_array($options['proxy'])) {
            $options['proxy'] = new Http($options['proxy']);
        }

        if ($options['proxy'] !== false) {
            $options['proxy']->register($options['hooks']);
        }

        if (is_array($options['cookies'])) {
            $options['cookies'] = new Jar($options['cookies']);
        } elseif (empty($options['cookies'])) {
            $options['cookies'] = new Jar();
        }

        if ($options['cookies'] !== false) {
            $options['cookies']->register($options['hooks']);
        }

        if ($options['idn'] !== false) {
            $iri       = new Iri($url);
            $iri->host = IdnaEncoder::encode($iri->ihost);
            $url       = $iri->uri;
        }

        // Massage the type to ensure we support it.
        $type = strtoupper($type);

        if (!isset($options['data_format'])) {
            if (in_array($type, [self::HEAD, self::GET, self::DELETE], true)) {
                $options['data_format'] = 'query';
            } else {
                $options['data_format'] = 'body';
            }
        }
    }

    /**
     * HTTP response parser
     *
     * @param string $headers     Full response text including headers and body
     * @param string $url         Original request URL
     * @param array  $req_headers Original $headers array passed to {@link request()}, in case we need to follow redirects
     * @param array  $req_data    Original $data array passed to {@link request()}, in case we need to follow redirects
     * @param array  $options     Original $options array passed to {@link request()}, in case we need to follow redirects
     * @return Response
     * @throws HttpException On missing head/body separator (`requests.no_crlf_separator`)
     * @throws HttpException On missing head/body separator (`noversion`)
     * @throws HttpException On missing head/body separator (`toomanyredirects`)
     */
    protected static function parse_response(
        string $headers,
        string $url,
        array $req_headers,
        array $req_data,
        array $options
    ): Response
    {
        $return = new Response();
        if (!$options['blocking']) {
            return $return;
        }

        $return->raw  = $headers;
        $return->url  = $url;
        $return->body = '';

        if (!$options['filename']) {
            $pos = strpos($headers, "\r\n\r\n");
            if ($pos === false) {
                // Crap!
                throw new HttpException('Missing header/body separator', 'requests.no_crlf_separator');
            }

            $headers = substr($return->raw, 0, $pos);
            // Headers will always be separated from the body by two new lines - `\n\r\n\r`.
            $body = substr($return->raw, $pos + 4);
            if (!empty($body)) {
                $return->body = $body;
            }
        }

        // Pretend CRLF = LF for compatibility (RFC 2616, section 19.3)
        $headers = str_replace("\r\n", "\n", $headers);
        // Unfold headers (replace [CRLF] 1*( SP | HT ) with SP) as per RFC 2616 (section 2.2)
        $headers = preg_replace('/\n[ \t]/', ' ', $headers);
        $headers = explode("\n", $headers);
        preg_match('#^HTTP/(1\.\d)[ \t]+(\d+)#i', array_shift($headers), $matches);
        if (empty($matches)) {
            throw new HttpException('Response could not be parsed', 'noversion', $headers);
        }

        $return->protocol_version = (float) $matches[1];
        $return->status_code      = (int) $matches[2];
        if ($return->status_code >= 200 && $return->status_code < 300) {
            $return->success = true;
        }

        foreach ($headers as $header) {
            list($key, $value) = explode(':', $header, 2);
            $value             = trim($value);
            preg_replace('#(\s+)#i', ' ', $value);
            $return->headers[$key] = $value;
        }

        if (isset($return->headers['transfer-encoding'])) {
            $return->body = self::decodeChunked($return->body);
            unset($return->headers['transfer-encoding']);
        }

        if (isset($return->headers['content-encoding'])) {
            $return->body = self::decompress($return->body);
        }

        //fsockopen and cURL compatibility
        if (isset($return->headers['connection'])) {
            unset($return->headers['connection']);
        }

        $options['hooks']->dispatch('requests.before_redirect_check', [&$return, $req_headers, $req_data, $options]);

        if ($return->isRedirect() && $options['follow_redirects'] === true) {
            if (isset($return->headers['location']) && $options['redirected'] < $options['redirects']) {
                if ($return->status_code === 303) {
                    $options['type'] = self::GET;
                }

                ++$options['redirected'];
                $location = $return->headers['location'];
                if (!str_starts_with($location, 'http://') && !str_starts_with($location, 'https://')) {
                    // relative redirect, for compatibility make it absolute
                    $location = Iri::absolutize($url, $location);
                    $location = $location->uri;
                }

                $hook_args = [
                    &$location,
                    &$req_headers,
                    &$req_data,
                    &$options,
                    $return,
                ];
                $options['hooks']->dispatch('requests.before_redirect', $hook_args);
                $redirected            = self::request($location, $req_headers, $req_data, $options['type'], $options);
                $redirected->history[] = $return;
                return $redirected;
            } elseif ($options['redirected'] >= $options['redirects']) {
                throw new HttpException('Too many redirects', 'toomanyredirects', $return);
            }
        }

        $return->redirects = $options['redirected'];

        $options['hooks']->dispatch('requests.after_request', [&$return, $req_headers, $req_data, $options]);
        return $return;
    }

    /**
     * Callback for `transport.internal.parse_response`
     * Internal use only. Converts a raw HTTP response to a Response while still executing a multiple request.
     * `$response` is either set to a Response instance, or an Exception object
     *
     * @param string $response Full response text including headers and body (will be overwritten with Response instance)
     * @param array  $request  Request data as passed into {@see Requests::requestMultiple()}
     * @return void
     */
    public static function parseMultiple(string &$response, array $request): void
    {
        try {
            $url      = $request['url'];
            $headers  = $request['headers'];
            $data     = $request['data'];
            $options  = $request['options'];
            $response = self::parse_response($response, $url, $headers, $data, $options);
        } catch (HttpException $e) {
            $response = $e;
        }
    }

    /**
     * Decoded a chunked body as per RFC 2616
     *
     * @link https://tools.ietf.org/html/rfc2616#section-3.6.1
     * @param string $data Chunked body
     * @return string Decoded body
     */
    protected static function decodeChunked(string $data): string
    {
        if (!preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', trim($data))) {
            return $data;
        }

        $decoded = '';
        $encoded = $data;

        while (true) {
            $is_chunked = (bool) preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', $encoded, $matches);
            if (!$is_chunked) {
                return $data;
            }

            $length = hexdec(trim($matches[1]));
            if ($length === 0) {
                // Ignore trailer headers
                return $decoded;
            }

            $chunk_length = strlen($matches[0]);
            $decoded     .= substr($encoded, $chunk_length, $length);
            $encoded      = substr($encoded, $chunk_length + $length + 2);

            if (trim($encoded) === '0' || empty($encoded)) {
                return $decoded;
            }
        }
    }

    /**
     * Convert a key => value array to a 'key: value' array for headers
     *
     * @param iterable $dictionary Dictionary of header values
     * @return array List of headers
     *
     * @throws InvalidArgument When the passed argument is not iterable.
     */
    public static function flatten(iterable $dictionary): array
    {
        if (is_iterable($dictionary) === false) {
            throw InvalidArgument::create(1, '$dictionary', 'iterable', gettype($dictionary));
        }

        $return = [];
        foreach ($dictionary as $key => $value) {
            $return[] = sprintf('%s: %s', $key, $value);
        }

        return $return;
    }

    /**
     * Decompress an encoded body
     * Implements gzip, compress and deflate. Guesses which it is by attempting
     * to decode.
     *
     * @param string $data Compressed data in one of the above formats
     * @return bool|string Decompressed string
     */
    public static function decompress(string $data): bool|string
    {
        if (trim($data) === '') {
            // Empty body does not need further processing.
            return $data;
        }

        $marker = substr($data, 0, 2);
        if (!isset(self::$magic_compression_headers[$marker])) {
            // Not actually compressed. Probably cURL ruining this for us.
            return $data;
        }

        if (function_exists('gzdecode')) {
            $decoded = @gzdecode($data);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        if (function_exists('gzinflate')) {
            $decoded = @gzinflate($data);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        $decoded = self::compatibleGzinflate($data);
        if ($decoded !== false) {
            return $decoded;
        }

        if (function_exists('gzuncompress')) {
            $decoded = @gzuncompress($data);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $data;
    }

    /**
     * Decompression of deflated string while staying compatible with the majority of servers.
     *
     * Certain Servers will return deflated data with headers which PHP's gzinflate()
     * function cannot handle out of the box. The following function has been created from
     * various snippets on the gzinflate() PHP documentation.
     *
     * Warning: Magic numbers within. Due to the potential different formats that the compressed
     * data may be returned in, some "magic offsets" are needed to ensure proper decompression
     * takes place. For a simple progmatic way to determine the magic offset in use, see:
     *
     * @link https://www.php.net/gzinflate#70875
     * @link https://www.php.net/gzinflate#77336
     *
     * @param string $gz_data String to decompress.
     * @return string|bool False on failure.
     */
    public static function compatibleGzinflate(string $gz_data): string|bool
    {
        if (trim($gz_data) === '') {
            return false;
        }

        // Compressed data might contain a full zlib header, if so strip it for gzinflate()
        if (str_starts_with($gz_data, "\x1f\x8b\x08")) {
            $i   = 10;
            $flg = ord(substr($gz_data, 3, 1));
            if ($flg > 0) {
                if ($flg & 4) {
                    list($xlen) = unpack('v', substr($gz_data, $i, 2));
                    $i         += 2 + $xlen;
                }

                if ($flg & 8) {
                    $i = strpos($gz_data, "\0", $i) + 1;
                }

                if ($flg & 16) {
                    $i = strpos($gz_data, "\0", $i) + 1;
                }

                if ($flg & 2) {
                    $i += 2;
                }
            }

            $decompressed = self::compatibleGzinflate(substr($gz_data, $i));
            if ($decompressed !== false) {
                return $decompressed;
            }
        }

        // If the data is Huffman Encoded, we must first strip the leading 2 byte Huffman marker for gzinflate()
        // The response is Huffman coded by many compressors such as java.util.zip.Deflater, Ruby's Zlib::Deflate,
        // and .NET's System.IO.Compression.DeflateStream.
        //
        // See https://decompres.blogspot.com/ for a quick explanation of this data type
        $huffman_encoded = false;

        // low nibble of first byte should be 0x08
        list(, $first_nibble) = unpack('h', $gz_data);

        // First 2 bytes should be divisible by 0x1F
        list(, $first_two_bytes) = unpack('n', $gz_data);

        if ($first_nibble === 0x08 && ($first_two_bytes % 0x1F) === 0) {
            $huffman_encoded = true;
        }

        if ($huffman_encoded) {
            $decompressed = @gzinflate(substr($gz_data, 2));
            if ($decompressed !== false) {
                return $decompressed;
            }
        }

        if (str_starts_with($gz_data, "\x50\x4b\x03\x04")) {
            // ZIP file format header
            // Offset 6: 2 bytes, General-purpose field
            // Offset 26: 2 bytes, filename length
            // Offset 28: 2 bytes, optional field length
            // Offset 30: Filename field, followed by optional field, followed
            // immediately by data
            list(, $general_purpose_flag) = unpack('v', substr($gz_data, 6, 2));

            // If the file has been compressed on the fly, 0x08 bit is set of
            // the general purpose field. We can use this to differentiate
            // between a compressed document, and a ZIP file
            $zip_compressed_on_the_fly = ((0x08 & $general_purpose_flag) === 0x08);

            if (!$zip_compressed_on_the_fly) {
                // Don't attempt to decode a compressed zip file
                return $gz_data;
            }

            // Determine the first byte of data, based on the above ZIP header
            // offsets:
            $first_file_start = array_sum(unpack('v2', substr($gz_data, 26, 4)));
            $decompressed     = @gzinflate(substr($gz_data, 30 + $first_file_start));
            if ($decompressed !== false) {
                return $decompressed;
            }

            return false;
        }

        // Finally fall back to straight gzinflate
        $decompressed = @gzinflate($gz_data);
        if ($decompressed !== false) {
            return $decompressed;
        }

        // Fallback for all above failing, not expected, but included for
        // debugging and preventing regressions and to track stats
        $decompressed = @gzinflate(substr($gz_data, 2));
        if ($decompressed !== false) {
            return $decompressed;
        }

        return false;
    }
}
