<?php

declare(strict_types=1);

namespace Expansa\Support;

use RuntimeException;
use Throwable;

class Url
{
    /**
     * Directory served at the site URL, with a trailing slash.
     */
    private static string $root = '';

    /**
     * @var callable|null
     */
    private static $site = null;

    /**
     * Set the directory served at the site URL and the source of that URL.
     * Without $site, or when it fails, the URL is built from the request.
     *
     * @param callable(): string|null $site
     */
    public static function configure(string $root, ?callable $site = null): void
    {
        self::$root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        self::$site = $site;
    }

    /**
     * Absolute path of a file under the root, e.g. "cache/views" or the path part of an URL.
     */
    public static function toPath(string $relative = ''): string
    {
        return self::$root . ltrim($relative, '/\\');
    }

    /**
     * Site URL of a file under the root; a path outside of it is treated as relative.
     */
    public static function toUrl(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        $relative = self::$root !== '' && str_starts_with($path, self::$root) ? substr($path, strlen(self::$root)) : ltrim($path, '/');

        return url($relative);
    }

    /**
     * Retrieves the URL for a given site where Expansa application files are accessible.
     *
     * @param string $path Optional. Path relative to the site URL. Default empty.
     * @return string Site URL link with optional path appended.
     */
    public function site(string $path = ''): string
    {
        try {
            if (self::$site === null) {
                throw new RuntimeException(t('The site URL source is not configured.'));
            }

            $url = (self::$site)();
        } catch (Throwable $e) {
            $protocol = match (true) {
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                &&
                $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' => 'https://',
                default                                        => 'http://',
            };

            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $url  = $protocol . filter_var($host, FILTER_SANITIZE_URL);
        } finally {
            $url = rtrim($url, '/') . '/';
            if ($path) {
                $url .= ltrim($path, '/');
            }

            return $url;
        }
    }
}
