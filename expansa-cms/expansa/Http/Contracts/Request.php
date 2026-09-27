<?php

declare(strict_types=1);

namespace Expansa\Http\Contracts;

use Closure;

/**
 * Incoming HTTP request. Input methods read the merged $input by top-level keys.
 *
 * @package Expansa\Http
 */
interface Request
{
    /**
     * Query string values.
     *
     * @var array<array-key, mixed>
     */
    public array $query { get; }

    /**
     * Form body values.
     *
     * @var array<array-key, mixed>
     */
    public array $post { get; }

    /**
     * Cookies sent by the client.
     *
     * @var array<string, string>
     */
    public array $cookies { get; }

    /**
     * Uploaded files in the $_FILES form.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $files { get; }

    /**
     * Server and environment values in the $_SERVER form.
     *
     * @var array<string, mixed>
     */
    public array $server { get; }

    /**
     * Headers by name in the $_SERVER form: CONTENT_TYPE, USER_AGENT.
     *
     * @var array<string, string>
     */
    public array $headers { get; }

    /**
     * Raw request body.
     *
     * @var string
     */
    public string $content { get; }

    /**
     * Body decoded as a JSON object, empty if it is not one.
     *
     * @var array<array-key, mixed>
     */
    public array $json { get; }

    /**
     * Query, form, JSON and file values merged, later sources override earlier ones.
     *
     * @var array<array-key, mixed>
     */
    public array $input { get; }

    /**
     * Accept header types in the order sent.
     *
     * @var string[]
     */
    public array $acceptableTypes { get; }

    /**
     * Accept-Language languages from the most preferred: en_us.
     *
     * @var string[]
     */
    public array $languages { get; }

    /**
     * Request method.
     */
    public string $method { get; }

    /**
     * Whether the request came over HTTPS.
     *
     * @var bool
     */
    public bool $secure { get; }

    /**
     * "https" or "http".
     *
     * @var string
     */
    public string $scheme { get; }

    /**
     * Host, with the port if it is not the default one.
     *
     * @var string
     */
    public string $host { get; }

    /**
     * Port.
     *
     * @var int
     */
    public int $port { get; }

    /**
     * Request URI as sent.
     *
     * @var string
     */
    public string $uri { get; }

    /**
     * URI path without the query string and the trailing slash.
     *
     * @var string
     */
    public string $path { get; }

    /**
     * Query string without "?".
     *
     * @var string
     */
    public string $queryString { get; }

    /**
     * Scheme and host.
     *
     * @var string
     */
    public string $root { get; }

    /**
     * URL without the query string.
     *
     * @var string
     */
    public string $url { get; }

    /**
     * Client IP.
     *
     * @var string
     */
    public string $ip { get; }

    /**
     * User-Agent header.
     *
     * @var string
     */
    public string $userAgent { get; }

    /**
     * Token of the "Authorization: Bearer" header.
     *
     * @var ?string
     */
    public ?string $bearerToken { get; }

    /**
     * User of HTTP basic authentication.
     *
     * @var ?string
     */
    public ?string $authUser { get; }

    /**
     * Password of HTTP basic authentication.
     *
     * @var ?string
     */
    public ?string $authPassword { get; }

    /**
     * Whether the request was sent with XMLHttpRequest.
     *
     * @var bool
     */
    public bool $isAjax { get; }

    /**
     * Whether the request was sent by PJAX.
     *
     * @var bool
     */
    public bool $isPjax { get; }

    /**
     * Whether the browser prefetches the page.
     *
     * @var bool
     */
    public bool $isPrefetch { get; }

    /**
     * Route matched for the request.
     *
     * @var null|Route
     */
    public ?Route $route { get; set; }

    /**
     * Session for old input.
     *
     * @var null|Session
     */
    public ?Session $session { get; set; }

    /**
     * Resolver of the current user: fn (?string $guard): mixed.
     *
     * @var null|Closure
     */
    public ?Closure $userResolver { get; set; }

    /**
     * Create the request from the superglobals.
     *
     * @return static
     */
    public static function createFromGlobals(): static;

    /**
     * Create a request to the URI, e.g. for tests or internal calls.
     * Parameters go to the query for GET and HEAD, to the form body otherwise.
     *
     * @param string                  $uri        Absolute URL or path, may contain a query string.
     * @param string                  $method
     * @param array<array-key, mixed> $parameters
     * @param array<string, string>   $cookies
     * @param array<string, mixed>    $files
     * @param array<string, mixed>    $server     Overrides the default $_SERVER values.
     * @param string|null             $content    Raw body.
     * @return static
     */
    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
    ): static;

    /**
     * Whether the method is one of the given, in any case.
     *
     * @param string ...$methods
     * @return bool
     */
    public function isMethod(string ...$methods): bool;

    /**
     * URL with the current query string merged with the given values.
     *
     * @param array<string, mixed> $query
     * @return string
     */
    public function getFullUrl(array $query = []): string;

    /**
     * Header value by name in any case: "Content-Type" or "CONTENT_TYPE".
     *
     * @param string      $name
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $name, ?string $default = null): ?string;

    /**
     * Whether all the headers are sent.
     *
     * @param string ...$names
     * @return bool
     */
    public function hasHeader(string ...$names): bool;

    /**
     * Input value by key.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Input value as a trimmed string, the default for arrays, booleans and missing keys.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getString(string $key, string $default = ''): string;

    /**
     * Input value as an integer, the default if it is not one.
     *
     * @param string $key
     * @param int    $default
     * @return int
     */
    public function getInt(string $key, int $default = 0): int;

    /**
     * Input value as a float, the default if it is not a number.
     *
     * @param string $key
     * @param float  $default
     * @return float
     */
    public function getFloat(string $key, float $default = 0.0): float;

    /**
     * Input value as a boolean: "1", "true", "on", "yes" are true, "0", "false", "off", "no", "" are false.
     *
     * @param string $key
     * @param bool   $default Returned for missing keys and other values.
     * @return bool
     */
    public function getBool(string $key, bool $default = false): bool;

    /**
     * Whether the input has all the keys.
     *
     * @param string ...$keys
     * @return bool
     */
    public function has(string ...$keys): bool;

    /**
     * Whether the input has any of the keys.
     *
     * @param string ...$keys
     * @return bool
     */
    public function hasAny(string ...$keys): bool;

    /**
     * Input with the given keys only.
     *
     * @param string ...$keys
     * @return array<array-key, mixed>
     */
    public function only(string ...$keys): array;

    /**
     * Input without the given keys.
     *
     * @param string ...$keys
     * @return array<array-key, mixed>
     */
    public function except(string ...$keys): array;

    /**
     * Whether all the values are filled: not null, blank string or empty array.
     *
     * @param string ...$keys
     * @return bool
     */
    public function isFilled(string ...$keys): bool;

    /**
     * Whether any of the values is filled.
     *
     * @param string ...$keys
     * @return bool
     */
    public function isAnyFilled(string ...$keys): bool;

    /**
     * Whether none of the values is filled.
     *
     * @param string ...$keys
     * @return bool
     */
    public function isEmpty(string ...$keys): bool;

    /**
     * Call the callback with the value if the key is present, otherwise the default callback.
     *
     * @param string        $key
     * @param callable      $callback fn (mixed $value)
     * @param callable|null $default
     * @return static
     */
    public function whenHas(string $key, callable $callback, ?callable $default = null): static;

    /**
     * Call the callback with the value if it is filled, otherwise the default callback.
     *
     * @param string        $key
     * @param callable      $callback fn (mixed $value)
     * @param callable|null $default
     * @return static
     */
    public function whenFilled(string $key, callable $callback, ?callable $default = null): static;

    /**
     * Call the callback if the key is missing, otherwise the default callback.
     *
     * @param string        $key
     * @param callable      $callback
     * @param callable|null $default
     * @return static
     */
    public function whenMissing(string $key, callable $callback, ?callable $default = null): static;

    /**
     * Whether the client accepts any of the content types; true without an Accept header.
     *
     * @param string ...$types
     * @return bool
     */
    public function accepts(string ...$types): bool;

    /**
     * Whether the client accepts any content type first.
     *
     * @return bool
     */
    public function acceptsAny(): bool;

    /**
     * Whether the client accepts JSON.
     *
     * @return bool
     */
    public function acceptsJson(): bool;

    /**
     * Whether the client accepts HTML.
     *
     * @return bool
     */
    public function acceptsHtml(): bool;

    /**
     * First accepted format: html, json, xml, ...; the default if none.
     *
     * @param string $default
     * @return string
     */
    public function getFormat(string $default = 'html'): string;

    /**
     * Whether a JSON response suits the request: an AJAX call accepting anything, or wantsJson().
     *
     * @return bool
     */
    public function expectsJson(): bool;

    /**
     * Whether JSON is the first accepted type.
     *
     * @return bool
     */
    public function wantsJson(): bool;

    /**
     * Accepted languages from the most preferred, only the supported ones if given.
     *
     * @param string ...$supported
     * @return string[]
     */
    public function getLanguages(string ...$supported): array;

    /**
     * Whether the client accepts the language: "en-US" or "en_us".
     *
     * @param string $language
     * @return bool
     */
    public function acceptsLanguage(string $language): bool;

    /**
     * Current user from the resolver, null without it.
     *
     * @param string|null $guard
     * @return mixed
     */
    public function getUser(?string $guard = null): mixed;

    /**
     * Input value flashed by the previous request, the default without a session.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getOld(string $key, mixed $default = null): mixed;

    /**
     * Flash the whole input for the next request.
     *
     * @return void
     */
    public function flash(): void;

    /**
     * Flash the input with the given keys only.
     *
     * @param string ...$keys
     * @return void
     */
    public function flashOnly(string ...$keys): void;

    /**
     * Flash the input without the given keys.
     *
     * @param string ...$keys
     * @return void
     */
    public function flashExcept(string ...$keys): void;

    /**
     * Forget the flashed input.
     *
     * @return void
     */
    public function flushOld(): void;
}
