<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimum server requirements shared by the bootstrap and the installer.
 * Runs before expansa/functions.php, so keep it (and dashboard/error.php) free of PHP 8.4 syntax and helpers.
 */
final class Requirements
{
    public static function php(): bool
    {
        return version_compare(PHP_VERSION, EX_REQUIRED_PHP_VERSION, '>=');
    }

    public static function database(string $version): bool
    {
        return version_compare($version, EX_REQUIRED_MYSQL_VERSION, '>=');
    }

    /**
     * Render the requirements error page and stop, if the PHP version is too old.
     * The database version is checked by the installer, not on every request.
     */
    public static function check(): void
    {
        if (self::php()) {
            return;
        }

        $message = sprintf(
            'Your server is running PHP version "%s" but Expansa %s requires at least %s.',
            PHP_VERSION,
            EX_VERSION,
            EX_REQUIRED_PHP_VERSION
        );

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }

        $serverProtocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
        if (! in_array($serverProtocol, ['HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3'], true)) {
            $serverProtocol = 'HTTP/1.0';
        }

        if (! headers_sent()) {
            header(sprintf('%s 500 Internal Server Error', $serverProtocol), true, 500);
            header('Content-Type: text/html; charset=utf-8');
        }

        $baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        require EX_PATH . 'dashboard/error.php';
        exit;
    }
}
