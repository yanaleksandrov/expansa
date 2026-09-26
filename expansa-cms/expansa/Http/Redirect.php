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
     * Set the filters, a repeated call replaces all of them.
     *
     * @param Closure|null $location   fn (string $to, int $status): string
     * @param Closure|null $status     fn (int $status, string $to): int
     * @param Closure|null $redirectBy fn (string $redirectBy, int $status, string $to): string
     * @return void
     */
    public static function configure(?Closure $location = null, ?Closure $status = null, ?Closure $redirectBy = null): void
    {
        self::$locationFilter   = $location;
        self::$statusFilter     = $status;
        self::$redirectByFilter = $redirectBy;
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

        if ($redirectBy !== '') {
            header("X-Redirect-By: $redirectBy");
        }

        header("Location: $location", true, $code);
        exit;
    }
}
