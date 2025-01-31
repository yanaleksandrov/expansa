<?php

namespace Expansa\Http\Transport;

use Stringable;
use CurlHandle;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Expansa\Http\Hooks;
use Expansa\Http\Contracts\Capability;
use Expansa\Http\Exception\HttpException;
use Expansa\Http\Exception\InvalidArgument;
use Expansa\Http\Exception\Transport\Curl as CurlException;
use Expansa\Http\Requests;
use Expansa\Http\Contracts\Transport;
use Expansa\Http\Utility\InputValidator;

/**
 * HTTP transport using libcurl.
 *
 * @package Expansa\Http
 */
final class Curl implements Transport
{
    public const CURL_7_10_5 = 0x070A05;

    public const CURL_7_16_2 = 0x071002;

    /**
     * Raw HTTP data
     *
     * @var string
     */
    public string $headers = '';

    /**
     * Raw body data
     *
     * @var string
     */
    public string $responseData = '';

    /**
     * Information on the current request
     *
     * @var array cURL information array, see {@link https://www.php.net/curl_getinfo}
     */
    public array $info;

    /**
     * cURL version number
     *
     * @var int
     */
    public int $version;

    /**
     * cURL handle
     *
     * @var false|CurlHandle Instance of CurlHandle in PHP >= 8.0.
     */
    private false|CurlHandle $handle;

    /**
     * Hook dispatcher instance
     *
     * @var Hooks
     */
    private Hooks $hooks;

    /**
     * Have we finished the headers yet?
     *
     * @var bool
     */
    private bool $doneHeaders = false;

    /**
     * If streaming to a file, keep the file pointer
     *
     * @var resource
     */
    private mixed $streamHandle;

    /**
     * How many bytes are in the response body?
     *
     * @var int
     */
    private int $responseBytes;

    /**
     * What's the maximum number of bytes we should keep?
     *
     * @var int|bool Byte count, or false if no limit.
     */
    private int|bool $responseByteLimit;

    /**
     * Constructor
     */
    public function __construct()
    {
        $curl          = curl_version();
        $this->version = $curl['version_number'];
        $this->handle  = curl_init();

        curl_setopt($this->handle, CURLOPT_HEADER, false);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        if ($this->version >= self::CURL_7_10_5) {
            curl_setopt($this->handle, CURLOPT_ENCODING, '');
        }

        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($this->handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            curl_setopt($this->handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->handle instanceof CurlHandle) {
            curl_close($this->handle);
        }
    }

    /**
     * Perform a request
     *
     * @param string|Stringable $url     URL to request
     * @param array             $headers Associative array of request headers
     * @param string|array      $data    Data to send as POST body or URL parameters for GET/HEAD
     * @param array             $options Request options.
     * @return string Raw HTTP result
     * @throws InvalidArgument When the passed $data parameter is not an array or string.
     * @throws HttpException       On a cURL error (`curlerror`)
     */
    public function request(
        string|Stringable $url,
        array $headers = [],
        array|string $data = [],
        array $options = []
    ): string
    {
        if (!is_array($data) && !is_string($data)) {
            if ($data === null) {
                $data = '';
            } else {
                throw InvalidArgument::create(3, '$data', 'array|string', gettype($data));
            }
        }

        $this->hooks = $options['hooks'];

        $this->setupHandle($url, $headers, $data, $options);

        $options['hooks']->dispatch('curl.before_send', [&$this->handle]);

        if ($options['filename'] !== false) {
            $this->streamHandle = @fopen($options['filename'], 'wb');
            if ($this->streamHandle === false) {
                $error = error_get_last();
                if (!is_array($error)) {
                    // Shouldn't be possible, but can happen in test situations.
                    $error = ['message' => 'Failed to open stream'];
                }

                throw new HttpException($error['message'], 'fopen');
            }
        }

        $this->responseData      = '';
        $this->responseBytes     = 0;
        $this->responseByteLimit = false;
        if ($options['max_bytes'] !== false) {
            $this->responseByteLimit = $options['max_bytes'];
        }

        if (isset($options['verify'])) {
            if ($options['verify'] === false) {
                curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, 0);
            } elseif (is_string($options['verify'])) {
                curl_setopt($this->handle, CURLOPT_CAINFO, $options['verify']);
            }
        }

        if (isset($options['verifyname']) && $options['verifyname'] === false) {
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, 0);
        }

        curl_exec($this->handle);
        $response = $this->responseData;

        $options['hooks']->dispatch('curl.after_send', []);

        $curl_errno = curl_errno($this->handle);

        if (
            $curl_errno === CURLE_WRITE_ERROR
            && $this->responseByteLimit
            && $this->responseBytes >= $this->responseByteLimit
        ) {
            // Not actually an error in this case. We've drained all the data from the request that we want.
            $curl_errno = false;
        }

        if ($curl_errno === CURLE_WRITE_ERROR || $curl_errno === CURLE_BAD_CONTENT_ENCODING) {
            // Reset encoding and try again
            curl_setopt($this->handle, CURLOPT_ENCODING, 'none');

            $this->responseData  = '';
            $this->responseBytes = 0;
            curl_exec($this->handle);
            $response = $this->responseData;
        }

        $this->processResponse($response, $options);

        // Need to remove the $this reference from the curl handle.
        // Otherwise, \Expansa\Http\Transport\Curl won't be garbage collected and the curl_close() will never be called.
        curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, null);

        return $this->headers;
    }

    /**
     * Send multiple requests simultaneously
     *
     * @param array $requests Request data
     * @param array $options  Global options
     * @return array Array of Response objects.
     * @throws InvalidArgument When the passed $requests argument is not an array or iterable object with array access.
     */
    public function requestMultiple(array $requests, array $options): array
    {
        // If you're not requesting, we can't get any responses ¯\_(ツ)_/¯
        if (empty($requests)) {
            return [];
        }

        if (InputValidator::hasArrayAccess($requests) === false || is_iterable($requests) === false) {
            throw InvalidArgument::create(1, '$requests', 'array|ArrayAccess&Traversable', gettype($requests));
        }

        $multiHandle = curl_multi_init();
        $subRequests = [];
        $subHandles  = [];

        $class = get_class($this);
        foreach ($requests as $id => $request) {
            $subRequests[$id] = new $class();
            $subHandles[$id]  = $subRequests[$id]->getSubrequestHandle(
                $request['url'],
                $request['headers'],
                $request['data'],
                $request['options']
            );
            $request['options']['hooks']->dispatch('curl.before_multi_add', [&$subHandles[$id]]);
            curl_multi_add_handle($multiHandle, $subHandles[$id]);
        }

        $completed       = 0;
        $responses       = [];
        $subRequestCount = count($subRequests);

        $request['options']['hooks']->dispatch('curl.before_multi_exec', [&$multiHandle]);

        do {
            $active = 0;

            do {
                $status = curl_multi_exec($multiHandle, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            $to_process = [];

            // Read the information as needed
            while ($done = curl_multi_info_read($multiHandle)) {
                $key = array_search($done['handle'], $subHandles, true);
                if (!isset($to_process[$key])) {
                    $to_process[$key] = $done;
                }
            }

            // Parse the finished requests before we start getting the new ones
            foreach ($to_process as $key => $done) {
                $options = $requests[$key]['options'];
                if ($done['result'] !== CURLE_OK) {
                    //get error string for handle.
                    $reason          = curl_error($done['handle']);
                    $exception       = new CurlException(
                        $reason,
                        CurlException::EASY,
                        $done['handle'],
                        $done['result']
                    );
                    $responses[$key] = $exception;
                    $options['hooks']->dispatch('transport.internal.parse_error', [&$responses[$key], $requests[$key]]);
                } else {
                    $responses[$key] = $subRequests[$key]->processResponse($subRequests[$key]->responseData, $options);

                    $options['hooks']->dispatch('transport.internal.parse_response', [&$responses[$key], $requests[$key]]);
                }

                curl_multi_remove_handle($multiHandle, $done['handle']);
                curl_close($done['handle']);

                if (!is_string($responses[$key])) {
                    $options['hooks']->dispatch('multiple.request.complete', [&$responses[$key], $key]);
                }

                ++$completed;
            }
        } while ($active || $completed < $subRequestCount);

        $request['options']['hooks']->dispatch('curl.after_multi_exec', [&$multiHandle]);

        curl_multi_close($multiHandle);

        return $responses;
    }

    /**
     * Get the cURL handle for use in a multi-request
     *
     * @param string       $url     URL to request
     * @param array        $headers Associative array of request headers
     * @param string|array $data    Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
     * @param array        $options Request options, see {@see Requests::response()} for documentation
     * @return CurlHandle Subrequest cURL handle
     */
    public function &getSubrequestHandle(string $url, array $headers, string|array $data, array $options): CurlHandle
    {
        $this->setupHandle($url, $headers, $data, $options);

        $options['hooks']->dispatch('curl.before_send', [&$this->handle]);

        if ($options['filename'] !== false) {
            $this->streamHandle = fopen($options['filename'], 'wb');
        }

        $this->responseData       = '';
        $this->responseBytes      = 0;
        $this->responseByteLimit = false;
        if ($options['max_bytes'] !== false) {
            $this->responseByteLimit = $options['max_bytes'];
        }

        $this->hooks = $options['hooks'];

        return $this->handle;
    }

    /**
     * Set up the cURL handle for the given data
     *
     * @param string       $url     URL to request
     * @param array        $headers Associative array of request headers
     * @param string|array $data    Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
     * @param array        $options Request options, see {@see Requests::response()} for documentation
     */
    private function setupHandle(string $url, array $headers, string|array $data, array $options): void
    {
        $options['hooks']->dispatch('curl.before_request', [&$this->handle]);

        // Force closing the connection for old versions of cURL (<7.22).
        if (!isset($headers['Connection'])) {
            $headers['Connection'] = 'close';
        }

        /**
         * Add "Expect" header.
         *
         * By default, cURL adds an "Expect: 100-Continue" to most requests. This header can
         * add as much as a second to the time it takes for cURL to perform a request. To
         * prevent this, we need to set an empty "Expect" header. To match the behaviour of
         * Guzzle, we'll add the empty header to requests that are smaller than 1 MB and use
         * HTTP/1.1.
         *
         * https://curl.se/mail/lib-2017-07/0013.html
         */
        if (!isset($headers['Expect']) && $options['protocol_version'] === 1.1) {
            $headers['Expect'] = $this->getExpectHeader($data);
        }

        $headers = Requests::flatten($headers);

        if (!empty($data)) {
            $data_format = $options['data_format'];

            if ($data_format === 'query') {
                $url  = self::formatGet($url, $data);
                $data = '';
            } elseif (!is_string($data)) {
                $data = http_build_query($data, '', '&');
            }
        }

        switch ($options['type']) {
            case Requests::POST:
                curl_setopt($this->handle, CURLOPT_POST, true);
                curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
                break;
            case Requests::HEAD:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $options['type']);
                curl_setopt($this->handle, CURLOPT_NOBODY, true);
                break;
            case Requests::TRACE:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $options['type']);
                break;
            case Requests::PATCH:
            case Requests::PUT:
            case Requests::DELETE:
            case Requests::OPTIONS:
            default:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $options['type']);
                if (!empty($data)) {
                    curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
                }
        }

        // cURL requires a minimum timeout of 1 second when using the system
        // DNS resolver, as it uses `alarm()`, which is second resolution only.
        // There's no way to detect which DNS resolver is being used from our
        // end, so we need to round up regardless of the supplied timeout.
        //
        // https://github.com/curl/curl/blob/4f45240bc84a9aa648c8f7243be7b79e9f9323a5/lib/hostip.c#L606-L609
        $timeout = max($options['timeout'], 1);

        if (is_int($timeout) || $this->version < self::CURL_7_16_2) {
            curl_setopt($this->handle, CURLOPT_TIMEOUT, ceil($timeout));
        } else {
            curl_setopt($this->handle, CURLOPT_TIMEOUT_MS, round($timeout * 1000));
        }

        if (is_int($options['connect_timeout']) || $this->version < self::CURL_7_16_2) {
            curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT, ceil($options['connect_timeout']));
        } else {
            curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT_MS, round($options['connect_timeout'] * 1000));
        }

        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_USERAGENT, $options['useragent']);
        if (!empty($headers)) {
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
        }

        if ($options['protocol_version'] === 1.1) {
            curl_setopt($this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        } else {
            curl_setopt($this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        }

        if ($options['blocking'] === true) {
            curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, [$this, 'streamHeaders']);
            curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, [$this, 'streamBody']);
            curl_setopt($this->handle, CURLOPT_BUFFERSIZE, Requests::BUFFER_SIZE);
        }
    }

    /**
     * Process a response
     *
     * @param string $response Response data from the body
     * @param array  $options  Request options
     * @return string|false HTTP response data including headers. False if non-blocking.
     * @throws HttpException If the request resulted in a cURL error.
     */
    public function processResponse(string $response, array $options): false|string
    {
        if ($options['blocking'] === false) {
            $options['hooks']->dispatch('curl.after_request', ['', []]);
            return false;
        }

        if ($options['filename'] !== false && $this->streamHandle) {
            fclose($this->streamHandle);
            $this->headers = trim($this->headers);
        } else {
            $this->headers .= $response;
        }

        $curl_errno = curl_errno($this->handle);
        if (
            $curl_errno === CURLE_WRITE_ERROR
            && $this->responseByteLimit
            && $this->responseBytes >= $this->responseByteLimit
        ) {
            // Not actually an error in this case. We've drained all the data from the request that we want.
            $curl_errno = false;
        }

        if ($curl_errno) {
            $error = sprintf(
                'cURL error %s: %s',
                curl_errno($this->handle),
                curl_error($this->handle)
            );
            throw new HttpException($error, 'curlerror', $this->handle);
        }

        $this->info = curl_getinfo($this->handle);

        $options['hooks']->dispatch('curl.after_request', [&$this->headers, &$this->info]);
        return $this->headers;
    }

    /**
     * Collect the headers as they are received
     *
     * @param string $headers Header string
     * @return int Length of provided header
     */
    public function streamHeaders(string $headers): int
    {
        // Why do we do this? cURL will send both the final response and any
        // interim responses, such as a 100 Continue. We don't need that.
        // (We may want to keep this somewhere just in case)
        if ($this->doneHeaders) {
            $this->headers     = '';
            $this->doneHeaders = false;
        }

        $this->headers .= $headers;

        if ($headers === "\r\n") {
            $this->doneHeaders = true;
        }

        return strlen($headers);
    }

    /**
     * Collect data as it's received
     *
     * @param string $data Body data
     * @return bool|int Length of provided data
     */
    public function streamBody(string $data): bool|int
    {
        $this->hooks->dispatch('request.progress', [$data, $this->responseBytes, $this->responseByteLimit]);
        $dataLength = strlen($data);

        // Are we limiting the response size?
        if ($this->responseByteLimit) {
            if (($this->responseBytes + $dataLength) > $this->responseByteLimit) {
                // Limit the length
                $dataLength = ($this->responseByteLimit - $this->responseBytes);
                $data       = substr($data, 0, $dataLength);
            }
        }

        if ($this->streamHandle) {
            if ($data !== '') {
                fwrite($this->streamHandle, $data);
            }
        } else {
            $this->responseData .= $data;
        }

        $this->responseBytes += $dataLength;

        return $dataLength;
    }

    /**
     * Format a URL given GET data
     *
     * @param string       $url  Original URL.
     * @param array|object $data Data to build query using, see {@link https://www.php.net/http_build_query}
     * @return string URL with data
     */
    private static function formatGet(string $url, array|object $data): string
    {
        if (!empty($data)) {
            $query     = '';
            $urlParts = parse_url($url);
            if (empty($urlParts['query'])) {
                $urlParts['query'] = '';
            } else {
                $query = $urlParts['query'];
            }

            $query .= '&' . http_build_query($data, '', '&');
            $query  = trim($query, '&');

            if (empty($urlParts['query'])) {
                $url .= '?' . $query;
            } else {
                $url = str_replace($urlParts['query'], $query, $url);
            }
        }

        return $url;
    }

    /**
     * Self-test whether the transport can be used.
     * The available capabilities to test for can be found in {@see Capability}.
     *
     * @codeCoverageIgnore
     * @param array<string, bool> $capabilities Optional. Associative array of capabilities to test against, i.e. `['<capability>' => true]`.
     * @return bool Whether the transport can be used.
     */
    public static function test(array $capabilities = []): bool
    {
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            return false;
        }

        // If needed, check that our installed curl version supports SSL
        if (isset($capabilities[Capability::SSL]) && $capabilities[Capability::SSL]) {
            $curl_version = curl_version();
            if (!(CURL_VERSION_SSL & $curl_version['features'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the correct "Expect" header for the given request data.
     *
     * @param string|array $data Data to send either as the POST body, or as parameters in the URL for a GET/HEAD.
     * @return string The "Expect" header.
     */
    private function getExpectHeader(string|array $data): string
    {
        if (!is_array($data)) {
            return strlen($data) >= 1048576 ? '100-Continue' : '';
        }

        $bytesize = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));

        foreach ($iterator as $datum) {
            $bytesize += strlen((string) $datum);

            if ($bytesize >= 1048576) {
                return '100-Continue';
            }
        }

        return '';
    }
}
