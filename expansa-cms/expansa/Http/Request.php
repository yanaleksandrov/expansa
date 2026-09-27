<?php

declare(strict_types=1);

namespace Expansa\Http;

use ArrayAccess;
use Closure;
use Expansa\Http\Contracts\Request as RequestContract;
use Expansa\Http\Contracts\Route;
use Expansa\Http\Contracts\Session;
use Expansa\Support\Traits\Macroable;
use LogicException;

/**
 * Incoming HTTP request: superglobal values as arrays plus what is derived from them.
 * Headers, body, decoded JSON and Accept values are read on first access, so a request
 * that only reads form values never touches php://input. Input is read-only: array
 * access and magic properties read $input, writing them throws LogicException.
 *
 * @implements ArrayAccess<string, mixed>
 * @package Expansa\Http
 */
final class Request implements ArrayAccess, RequestContract
{
    use Macroable;

    /**
     * Formats of getFormat() and their content types.
     */
    private const array FORMATS = [
        'html'   => ['text/html', 'application/xhtml+xml'],
        'txt'    => ['text/plain'],
        'js'     => ['application/javascript', 'application/x-javascript', 'text/javascript'],
        'css'    => ['text/css'],
        'json'   => ['application/json', 'application/x-json'],
        'jsonld' => ['application/ld+json'],
        'xml'    => ['text/xml', 'application/xml', 'application/x-xml'],
        'rdf'    => ['application/rdf+xml'],
        'atom'   => ['application/atom+xml'],
        'rss'    => ['application/rss+xml'],
        'form'   => ['application/x-www-form-urlencoded', 'multipart/form-data'],
    ];

    /**
     * Headers by name in the $_SERVER form: upper case, "-" replaced with "_" (CONTENT_TYPE, USER_AGENT).
     *
     * @var array<string, string>
     */
    public private(set) array $headers {
        get => $this->headers ??= self::extractHeaders($this->server);
    }

    /**
     * Raw request body, php://input is read on first access.
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
     * Accept header types in lower case, without parameters, in the order sent.
     *
     * @var string[]
     */
    public private(set) array $acceptableTypes {
        get => $this->acceptableTypes ??= self::parseAccept($this->headers['ACCEPT'] ?? '');
    }

    /**
     * Accept-Language languages from the most preferred, lower case with "_": en_us.
     *
     * @var string[]
     */
    public private(set) array $languages {
        get => $this->languages ??= self::parseLanguages($this->headers['ACCEPT_LANGUAGE'] ?? '');
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
     * "https" or "http".
     */
    public string $scheme {
        get => $this->secure ? 'https' : 'http';
    }

    /**
     * Host in lower case as the client sent it, with the port if it is not the default one.
     */
    public string $host {
        get => strtolower($this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? $this->server['SERVER_ADDR'] ?? '');
    }

    /**
     * Port from the Host header, the server port or the scheme default.
     */
    public int $port {
        get {
            if (preg_match('/:(\d+)$/', $this->host, $match)) {
                return (int) $match[1];
            }

            return isset($this->server['HTTP_HOST']) || ! isset($this->server['SERVER_PORT'])
                ? ($this->secure ? 443 : 80)
                : (int) $this->server['SERVER_PORT'];
        }
    }

    /**
     * Request URI as sent: path and query string.
     */
    public string $uri {
        get => $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * URI path without the query string and the trailing slash.
     */
    public string $path {
        get {
            $path = strtok($this->uri, '?');

            return $path === false || $path === '/' ? '/' : rtrim($path, '/');
        }
    }

    /**
     * Query string without "?".
     */
    public string $queryString {
        get => (string) ($this->server['QUERY_STRING'] ?? '');
    }

    /**
     * Scheme and host: https://example.com.
     */
    public string $root {
        get => $this->scheme . '://' . $this->host;
    }

    /**
     * Request URL without the query string.
     */
    public string $url {
        get => $this->root . ($this->path === '/' ? '' : $this->path);
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

    /**
     * User-Agent header.
     */
    public string $userAgent {
        get => $this->headers['USER_AGENT'] ?? '';
    }

    /**
     * Token of the "Authorization: Bearer" header.
     */
    public ?string $bearerToken {
        get {
            $header = $this->headers['AUTHORIZATION'] ?? '';
            $pos    = stripos($header, 'Bearer ');

            return $pos === false ? null : trim(explode(',', substr($header, $pos + 7), 2)[0]);
        }
    }

    /**
     * User of HTTP basic authentication.
     */
    public ?string $authUser {
        get => $this->server['PHP_AUTH_USER'] ?? $this->basicCredentials()[0] ?? null;
    }

    /**
     * Password of HTTP basic authentication.
     */
    public ?string $authPassword {
        get => $this->server['PHP_AUTH_PW'] ?? $this->basicCredentials()[1] ?? null;
    }

    /**
     * Whether the request was sent with XMLHttpRequest.
     */
    public bool $isAjax {
        get => ($this->headers['X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Whether the request was sent by PJAX.
     */
    public bool $isPjax {
        get => ($this->headers['X_PJAX'] ?? '') === 'true';
    }

    /**
     * Whether the browser prefetches the page.
     */
    public bool $isPrefetch {
        get => strcasecmp($this->headers['X_MOZ'] ?? '', 'prefetch') === 0
            || strcasecmp($this->headers['X_PURPOSE'] ?? '', 'preview') === 0
            || strcasecmp($this->headers['SEC_PURPOSE'] ?? '', 'prefetch') === 0;
    }

    /**
     * Route matched for the request, set by the router.
     */
    public ?Route $route = null;

    /**
     * Session for old input, set by the application.
     */
    public ?Session $session = null;

    /**
     * Resolver of the current user for getUser(): fn (?string $guard): mixed.
     */
    public ?Closure $userResolver = null;

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

    public static function createFromGlobals(): static
    {
        return new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
    ): static {
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

    public function isMethod(string ...$methods): bool
    {
        return in_array($this->method, array_map(strtoupper(...), $methods), true);
    }

    public function getFullUrl(array $query = []): string
    {
        parse_str($this->queryString, $current);

        $queryString = http_build_query([...$current, ...$query], '', '&', PHP_QUERY_RFC3986);

        return $queryString === '' ? $this->url : $this->url . ($this->path === '/' ? '/?' : '?') . $queryString;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers[self::headerKey($name)] ?? $default;
    }

    public function hasHeader(string ...$names): bool
    {
        return array_all($names, fn (string $name) => isset($this->headers[self::headerKey($name)]));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $default;
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_scalar($value) && ! is_bool($value) ? trim((string) $value) : $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return filter_var($this->get($key), FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        return filter_var($this->get($key), FILTER_VALIDATE_FLOAT, ['options' => ['default' => $default]]);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public function has(string ...$keys): bool
    {
        return array_all($keys, fn (string $key) => array_key_exists($key, $this->input));
    }

    public function hasAny(string ...$keys): bool
    {
        return array_any($keys, fn (string $key) => array_key_exists($key, $this->input));
    }

    public function only(string ...$keys): array
    {
        return array_intersect_key($this->input, array_flip($keys));
    }

    public function except(string ...$keys): array
    {
        return array_diff_key($this->input, array_flip($keys));
    }

    public function isFilled(string ...$keys): bool
    {
        return array_all($keys, fn (string $key) => ! self::isBlank($this->get($key)));
    }

    public function isAnyFilled(string ...$keys): bool
    {
        return array_any($keys, fn (string $key) => ! self::isBlank($this->get($key)));
    }

    public function isEmpty(string ...$keys): bool
    {
        return array_all($keys, fn (string $key) => self::isBlank($this->get($key)));
    }

    public function whenHas(string $key, callable $callback, ?callable $default = null): static
    {
        if ($this->has($key)) {
            $callback($this->input[$key]);
        } elseif ($default !== null) {
            $default();
        }

        return $this;
    }

    public function whenFilled(string $key, callable $callback, ?callable $default = null): static
    {
        if ($this->isFilled($key)) {
            $callback($this->input[$key]);
        } elseif ($default !== null) {
            $default();
        }

        return $this;
    }

    public function whenMissing(string $key, callable $callback, ?callable $default = null): static
    {
        if (! $this->has($key)) {
            $callback();
        } elseif ($default !== null) {
            $default();
        }

        return $this;
    }

    public function accepts(string ...$types): bool
    {
        if ($this->acceptableTypes === []) {
            return true;
        }

        foreach ($this->acceptableTypes as $accept) {
            if ($accept === '*/*' || $accept === '*') {
                return true;
            }

            foreach ($types as $type) {
                $type = strtolower($type);

                if ($accept === $type || $accept === strtok($type, '/') . '/*') {
                    return true;
                }

                // application/json also accepts application/ld+json
                [$group, $subtype] = explode('/', $accept, 2) + [1 => ''];
                if ($subtype !== '' && preg_match('#^' . preg_quote($group, '#') . '/.+\+' . preg_quote($subtype, '#') . '$#', $type)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function acceptsAny(): bool
    {
        return $this->acceptableTypes === [] || in_array($this->acceptableTypes[0], ['*/*', '*'], true);
    }

    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    public function getFormat(string $default = 'html'): string
    {
        foreach (self::FORMATS as $format => $types) {
            if ($this->accepts(...$types)) {
                return $format;
            }
        }

        return $default;
    }

    public function expectsJson(): bool
    {
        return ($this->isAjax && ! $this->isPjax && $this->acceptsAny()) || $this->wantsJson();
    }

    public function wantsJson(): bool
    {
        $type = $this->acceptableTypes[0] ?? '';

        return str_contains($type, '/json') || str_contains($type, '+json');
    }

    public function getLanguages(string ...$supported): array
    {
        if ($supported === []) {
            return $this->languages;
        }

        return array_values(array_intersect($this->languages, array_map(self::languageKey(...), $supported)));
    }

    public function acceptsLanguage(string $language): bool
    {
        return in_array(self::languageKey($language), $this->languages, true);
    }

    public function getUser(?string $guard = null): mixed
    {
        return $this->userResolver !== null ? ($this->userResolver)($guard) : null;
    }

    public function getOld(string $key, mixed $default = null): mixed
    {
        return $this->session !== null ? $this->session->getOldInput($key, $default) : $default;
    }

    public function flash(): void
    {
        $this->requireSession()->setOldInput($this->input);
    }

    public function flashOnly(string ...$keys): void
    {
        $this->requireSession()->setOldInput($this->only(...$keys));
    }

    public function flashExcept(string ...$keys): void
    {
        $this->requireSession()->setOldInput($this->except(...$keys));
    }

    public function flushOld(): void
    {
        $this->requireSession()->setOldInput([]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->input[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->input[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Request input is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Request input is read-only.');
    }

    /**
     * Input value by an undeclared property name: $request->title.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->input[$key] ?? null;
    }

    /**
     * Whether the input has a non-null value for an undeclared property name.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->input[$key]);
    }

    /**
     * Session for the flash methods.
     *
     * @return Session
     * @throws LogicException If no session is set.
     */
    private function requireSession(): Session
    {
        return $this->session ?? throw new LogicException('Session is not set on the request.');
    }

    /**
     * User and password of the "Authorization: Basic" header.
     *
     * @return array{0?: string, 1?: string}
     */
    private function basicCredentials(): array
    {
        $header = $this->headers['AUTHORIZATION'] ?? '';

        if (strncasecmp($header, 'Basic ', 6) !== 0) {
            return [];
        }

        $credentials = explode(':', (string) base64_decode(substr($header, 6)), 2);

        return count($credentials) === 2 ? $credentials : [];
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
            } elseif (isset($server['PHP_AUTH_DIGEST'])) {
                $headers['AUTHORIZATION'] = $server['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }

    /**
     * Accept header types.
     *
     * @param string $accept
     * @return string[]
     */
    private static function parseAccept(string $accept): array
    {
        $types = [];

        foreach (explode(',', $accept) as $type) {
            $type = strtolower(trim(explode(';', $type, 2)[0]));
            if ($type !== '') {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Accept-Language languages sorted by quality.
     *
     * @param string $accept
     * @return string[]
     */
    private static function parseLanguages(string $accept): array
    {
        if (! preg_match_all('/([\w-]+)\s*(?:;\s*q\s*=\s*(\d*\.?\d*))?/', $accept, $matches)) {
            return [];
        }

        $languages = [];
        foreach ($matches[1] as $i => $language) {
            $languages[self::languageKey($language)] = $matches[2][$i] === '' ? 1.0 : (float) $matches[2][$i];
        }
        arsort($languages);

        return array_map(strval(...), array_keys($languages));
    }

    /**
     * Key of the $headers array for a header name.
     *
     * @param string $name
     * @return string
     */
    private static function headerKey(string $name): string
    {
        return strtoupper(strtr($name, '-', '_'));
    }

    /**
     * Language in the $languages form: en-US → en_us.
     *
     * @param string $language
     * @return string
     */
    private static function languageKey(string $language): string
    {
        return strtolower(strtr($language, '-', '_'));
    }

    /**
     * Whether an input value counts as not filled: null, blank string or empty array.
     *
     * @param mixed $value
     * @return bool
     */
    private static function isBlank(mixed $value): bool
    {
        return $value === null || $value === [] || (is_string($value) && trim($value) === '');
    }
}
