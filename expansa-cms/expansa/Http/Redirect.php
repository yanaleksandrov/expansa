<?php

declare(strict_types=1);

namespace Expansa\Http;

use Closure;
use InvalidArgumentException;

/**
 * HTTP redirect: sends the Location and X-Redirect-By headers and stops the script.
 * Location, status and X-Redirect-By pass through the filters set in configure().
 *
 * @package Expansa\Http
 */
final class Redirect
{
    /**
     * Filter of the location, gets the location and status.
     */
    private static ?Closure $locationFilter = null;

    /**
     * Filter of the status code, gets the status and location.
     */
    private static ?Closure $statusFilter = null;

    /**
     * Filter of the X-Redirect-By header, gets the value, status and location.
     */
    private static ?Closure $redirectByFilter = null;

    /**
     * Storage of flashed values for the next request, gets the key and values.
     */
    private static ?Closure $flashStore = null;

    /**
     * Values to flash on the next send(), by key.
     *
     * @var array<string, array<array-key, mixed>>
     */
    private static array $flashed = [];

    /**
     * Set the filters and the flash storage, a repeated call replaces all of them.
     *
     * @param Closure|null $location   fn (string $to, int $status): string
     * @param Closure|null $status     fn (int $status, string $to): int
     * @param Closure|null $redirectBy fn (string $redirectBy, int $status, string $to): string
     * @param Closure|null $flash      fn (string $key, array $values): void, e.g. a session write
     * @return void
     */
    public static function configure(
        ?Closure $location = null,
        ?Closure $status = null,
        ?Closure $redirectBy = null,
        ?Closure $flash = null,
    ): void {
        self::$locationFilter   = $location;
        self::$statusFilter     = $status;
        self::$redirectByFilter = $redirectBy;
        self::$flashStore       = $flash;
    }

    /**
     * Keep the values for the request after the next redirect, e.g. form errors.
     * They are passed to the flash storage of configure() on send().
     *
     * @param string                  $key
     * @param array<array-key, mixed> $values
     * @return void
     */
    public static function flash(string $key, array $values): void
    {
        self::$flashed[$key] = $values;
    }

    /**
     * Redirect to the URL and exit; returns only if the location filter cancels it with an empty string.
     *
     * @param string $to         Absolute URL.
     * @param int    $status     3xx status code.
     * @param string $redirectBy X-Redirect-By header value, empty to omit it.
     * @return void
     * @throws InvalidArgumentException If the filtered status is not 3xx.
     */
    public static function send(string $to, int $status = 302, string $redirectBy = 'Expansa'): void
    {
        // filters come from hooks, so their results are cast
        $location   = self::$locationFilter ? (string) (self::$locationFilter)($to, $status) : $to;
        $code       = self::$statusFilter ? (int) (self::$statusFilter)($status, $to) : $status;
        $redirectBy = self::$redirectByFilter ? (string) (self::$redirectByFilter)($redirectBy, $status, $to) : $redirectBy;

        if ($location === '') {
            return;
        }

        if ($code < 300 || $code > 399) {
            throw new InvalidArgumentException('HTTP redirect status code must be a redirection code, 3xx.');
        }

        if (self::$flashStore !== null) {
            foreach (self::$flashed as $key => $values) {
                (self::$flashStore)($key, $values);
            }
        }
        self::$flashed = [];

        if ($redirectBy !== '') {
            header("X-Redirect-By: $redirectBy");
        }

        header("Location: $location", true, $code);
        exit;
    }

    /**
     * Redirect to the referer, or to the fallback if the client sent none.
     *
     * @param string $fallback   Absolute URL.
     * @param int    $status
     * @param string $redirectBy
     * @return void
     */
    public static function back(string $fallback = '/', int $status = 302, string $redirectBy = 'Expansa'): void
    {
        self::send($_SERVER['HTTP_REFERER'] ?? $fallback, $status, $redirectBy);
    }

    /**
     * Page that redirects after a countdown, for when the user should see a message first.
     * In the texts ":url" becomes the escaped URL and ":seconds" the delay.
     *
     * @param string $to      Absolute URL.
     * @param int    $seconds
     * @param string $title   Plain text.
     * @param string $text    HTML; the countdown updates its <strong> element.
     * @return string
     */
    public static function render(
        string $to,
        int $seconds = 7,
        string $title = 'Redirecting to :url',
        string $text = 'Redirecting to <a href=":url">:url</a> after <strong>:seconds</strong> seconds.',
    ): string {
        $url     = htmlspecialchars($to, ENT_QUOTES, 'UTF-8');
        $replace = [':url' => $url, ':seconds' => (string) $seconds];
        $title   = htmlspecialchars(strtr($title, [':url' => $to, ':seconds' => (string) $seconds]), ENT_QUOTES, 'UTF-8');
        $text    = strtr($text, $replace);

        return <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8" />
                <meta http-equiv="refresh" content="$seconds;url=$url" />
                <title>$title</title>
            </head>
            <body
                style="display: flex; place-items: center; place-content: center; height: 100dvh; margin: 0"
                onload="var t=$seconds; setInterval(() => document.querySelector('p strong').innerText = t--, 1000)"
            >
                <p>$text</p>
            </body>
            </html>
            HTML;
    }
}
