<?php

declare(strict_types=1);

namespace Expansa\Support;

use app\Option;
use Throwable;
use RuntimeException;

class Url
{
    /**
     * Retrieves the URL for a given site where Expansa application files are accessible.
     *
     * @param string $path Optional. Path relative to the site URL. Default empty.
     * @return string Site URL link with optional path appended.
     */
    public function site(string $path = ''): string
    {
        try {
            if (!class_exists(Option::class)) {
                throw new RuntimeException(t('The Option class is not defined.'));
            }

            $url = Option::get('site.url');
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
        }

        $url = rtrim($url, '/') . '/';
        if ($path) {
            $url .= ltrim($path, '/');
        }

        return $url;
    }
}
