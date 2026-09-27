<?php

declare(strict_types=1);

namespace Expansa\Support;

use Throwable;

/**
 * Paths and URLs of files under the site root.
 *
 * @package Expansa\Support
 */
final class Url
{
    /**
     * Directory served at the site URL, with a trailing slash.
     */
    private static string $root = '';

    /**
     * Source of the site URL.
     *
     * @var callable(): (string|null)|null
     */
    private static $site = null;

    /**
     * Set the directory served at the site URL and the source of that URL.
     * Without $site, or when it fails, the URL is built from the request.
     *
     * @param string                        $root
     * @param callable(): (string|null)|null $site
     * @return void
     */
    public static function configure(string $root, ?callable $site = null): void
    {
        self::$root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        self::$site = $site;
    }

    /**
     * Absolute path of a file under the root, e.g. "cache/views" or the path part of an URL.
     *
     * @param string $relative
     * @return string
     */
    public static function toPath(string $relative = ''): string
    {
        return self::$root . ltrim($relative, '/\\');
    }

    /**
     * Site URL of a file under the root; a path outside of it is treated as relative.
     *
     * @param string $path
     * @return string
     */
    public static function toUrl(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        $relative = self::$root !== '' && str_starts_with($path, self::$root) ? substr($path, strlen(self::$root)) : ltrim($path, '/');

        return self::site($relative);
    }

    /**
     * Site URL with a path. Without a source, or when it fails or returns nothing,
     * the URL is built from the request.
     *
     * @param string $path Path relative to the site URL.
     * @return string
     */
    public static function site(string $path = ''): string
    {
        try {
            $url = self::$site === null ? '' : (string) (self::$site)();
        } catch (Throwable) {
            $url = '';
        }

        return rtrim($url !== '' ? $url : self::fromRequest(), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Site URL taken from the request: scheme and host.
     *
     * @return string
     */
    private static function fromRequest(): string
    {
        $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $host  = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        return ($https ? 'https://' : 'http://') . filter_var($host, FILTER_SANITIZE_URL);
    }
}
