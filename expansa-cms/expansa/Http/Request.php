<?php

declare(strict_types=1);

namespace Expansa\Http;

/**
 * Incoming HTTP request: superglobal values as arrays plus what is derived from them.
 * Headers, body and decoded JSON are read on first access, so a request that
 * only reads form values never touches php://input.
 *
 * @package Expansa\Http
 */
final class Request
{
    /**
     * Headers by name in the $_SERVER form: upper case, "-" replaced with "_" (CONTENT_TYPE, USER_AGENT).
     *
     * @var array<string, string>
     */
    public private(set) array $headers {
        get => $this->headers ??= self::extractHeaders($this->server);
    }

    /**
     * Raw request body.
     */
    public private(set) string $content {
        get => $this->content ??= (string) file_get_contents('php://input');
    }

    /**
     * Body decoded as a JSON object, empty if it is not one.
     *
     * @var array<array-key, mixed>
     */
    public private(set) array $json {
        get => $this->json ??= is_array($json = json_decode($this->content, true)) ? $json : [];
    }

    /**
     * Query, form, JSON and file values merged, later sources override earlier ones.
     *
     * @var array<array-key, mixed>
     */
    public private(set) array $input {
        get => $this->input ??= array_merge($this->query, $this->post, $this->json, $this->files);
    }

    /**
     * Request method, GET if the server did not set it.
     */
    public string $method {
        get => $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Whether the request came over HTTPS, directly or through a proxy.
     */
    public bool $secure {
        get => in_array(strtolower((string) ($this->server['HTTPS'] ?? '')), ['on', '1'], true)
            || (int) ($this->server['SERVER_PORT'] ?? 0) === 443
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || ($this->server['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';
    }

    /**
     * Host in lower case as the client sent it, with the port if it is not the default one.
     */
    public string $host {
        get => strtolower($this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? $this->server['SERVER_ADDR'] ?? '');
    }

    /**
     * URI path without the query string and the trailing slash.
     */
    public string $path {
        get {
            $path = strtok($this->server['REQUEST_URI'] ?? '/', '?');

            return $path === false || $path === '/' ? '/' : rtrim($path, '/');
        }
    }

    /**
     * Request URL without the query string.
     */
    public string $url {
        get => ($this->secure ? 'https://' : 'http://') . $this->host . ($this->path === '/' ? '' : $this->path);
    }

    /**
     * Client IP: the first X-Forwarded-For address, the Cloudflare header or the remote address.
     */
    public string $ip {
        get {
            if (isset($this->server['HTTP_X_FORWARDED_FOR'])) {
                return trim(explode(',', $this->server['HTTP_X_FORWARDED_FOR'])[0]);
            }

            return $this->server['HTTP_CF_CONNECTING_IP'] ?? $this->server['REMOTE_ADDR'] ?? '';
        }
    }

    public function __construct(

        /**
         * Query string values.
         *
         * @var array<array-key, mixed>
         */
        public readonly array $query = [],

        /**
         * Form body values.
         *
         * @var array<array-key, mixed>
         */
        public readonly array $post = [],

        /**
         * Cookies sent by the client.
         *
         * @var array<string, string>
         */
        public readonly array $cookies = [],

        /**
         * Uploaded files in the $_FILES form.
         *
         * @var array<string, array<string, mixed>>
         */
        public readonly array $files = [],

        /**
         * Server and environment values in the $_SERVER form.
         *
         * @var array<string, mixed>
         */
        public readonly array $server = [],

        /**
         * Raw body; null reads php://input on first access.
         */
        ?string $content = null,
    ) {
        if ($content !== null) {
            $this->content = $content;
        }
    }

    /**
     * Create the request from the superglobals.
     *
     * @return self
     */
    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    /**
     * Create a request to the URI, e.g. for tests or internal calls.
     * Parameters go to the query for GET and HEAD, to the form body otherwise.
     *
     * @param string                   $uri        Absolute URL or path, may contain a query string.
     * @param string                   $method
     * @param array<array-key, mixed>  $parameters
     * @param array<string, string>    $cookies
     * @param array<string, mixed>     $files
     * @param array<string, mixed>     $server     Overrides the default $_SERVER values.
     * @param string|null              $content    Raw body.
     * @return self
     */
    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
    ): self {
        $url     = parse_url($uri) ?: [];
        $method  = strtoupper($method);
        $host    = ($url['host'] ?? 'localhost') . (isset($url['port']) ? ':' . $url['port'] : '');
        $inQuery = $method === 'GET' || $method === 'HEAD';

        parse_str($url['query'] ?? '', $query);
        if ($inQuery) {
            $query = array_replace($query, $parameters);
        }
        $queryString = http_build_query($query, '', '&');

        $server = array_replace([
            'SERVER_NAME'     => $url['host'] ?? 'localhost',
            'SERVER_PORT'     => $url['port'] ?? (($url['scheme'] ?? '') === 'https' ? 443 : 80),
            'HTTP_HOST'       => $host,
            'REMOTE_ADDR'     => '127.0.0.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD'  => $method,
            'REQUEST_URI'     => '/' . ltrim($url['path'] ?? '', '/') . ($queryString === '' ? '' : '?' . $queryString),
            'QUERY_STRING'    => $queryString,
            'REQUEST_TIME'    => time(),
        ], ($url['scheme'] ?? '') === 'https' ? ['HTTPS' => 'on'] : [], $server);

        return new self($query, $inQuery ? [] : $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Header value by name in any case: "Content-Type" or "CONTENT_TYPE".
     *
     * @param string      $name
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtoupper(strtr($name, '-', '_'))] ?? $default;
    }

    /**
     * Input value by key, see $input.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $default;
    }

    /**
     * Input value as a trimmed string, the default for arrays and missing keys.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_scalar($value) && ! is_bool($value) ? trim((string) $value) : $default;
    }

    /**
     * Input value as an integer, the default if it is not one.
     *
     * @param string $key
     * @param int    $default
     * @return int
     */
    public function getInt(string $key, int $default = 0): int
    {
        return filter_var($this->get($key), FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
    }

    /**
     * Input value as a float, the default if it is not a number.
     *
     * @param string $key
     * @param float  $default
     * @return float
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        return filter_var($this->get($key), FILTER_VALIDATE_FLOAT, ['options' => ['default' => $default]]);
    }

    /**
     * Input value as a boolean: "1", "true", "on", "yes" are true, "0", "false", "off", "no", "" are false.
     *
     * @param string $key
     * @param bool   $default Returned for missing keys and other values.
     * @return bool
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Headers from the $_SERVER values; restores Authorization that Apache passes only after a rewrite.
     *
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH' || $key === 'CONTENT_MD5') {
                $headers[$key] = $value;
            }
        }

        if (! isset($headers['AUTHORIZATION'])) {
            if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['AUTHORIZATION'] = $server['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($server['PHP_AUTH_USER'])) {
                $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($server['PHP_AUTH_USER'] . ':' . ($server['PHP_AUTH_PW'] ?? ''));
            }
        }

        return $headers;
    }
}
