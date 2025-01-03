<?php

declare(strict_types=1);

namespace Expansa;

final class Url
{
    /**
     * Add GET parameters to a URL.
     *
     * @param string $url    The URL to add parameters to
     * @param array  $params An associative array of parameter names and values to add to the URL
     *
     * @return string the modified URL with the new parameters
     */
    public function addQueryParams(string $url, array $params): string
    {
        $url   = parse_url($url);
        $query = [];
        if (isset($url['query'])) {
            parse_str($url['query'], $query);
        }

        $url['query'] = http_build_query(
            array_merge(
                $query,
                $params
            )
        );

        return implode(
            '',
            [
                $url['scheme'] ?? 'http',
                '://',
                $url['host'] ?? '',
                isset($url['port']) ? ':' . $url['port'] : '',
                $url['path'] ?? '',
                isset($url['query']) ? '?' . $url['query'] : '',
                isset($url['fragment']) ? '#' . $url['fragment'] : '',
            ]
        );
    }

    /**
     * Retrieves the URL for a given site where Expansa application files are accessible.
     * Returns the 'site_url' option with the appropriate protocol, 'https' if
     * is_ssl() and 'http' otherwise. If `$scheme` is 'http' or 'https', `is_ssl()` is overridden.
     *
     * @param string      $path   Optional. Path relative to the site URL. Default empty.
     * @param string|null $scheme Optional. Scheme to give the site URL context. Accepts
     *                            'http', 'https', 'login', 'login_post', 'admin', or
     *                            'relative'. Default null.
     *
     * @return string site URL link with optional path appended
     */
    public static function site(string $path = '', string $scheme = null): string
    {
        try {
            $url = Option::get('site.url');
        } catch (\Error | \PDOException $e) {
            $protocol = match (true) {
                ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' => 'https://',
                default => 'http://',
            };

            $url = $protocol . ( $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] );
        }

        $url = sprintf('%s/', $url);
        if ($path) {
            $url .= ltrim($path, '/');
        }

        /*
         * Filters the site URL.
         *
         * @param string      $url     The complete site URL including scheme and path.
         * @param string      $path    Path relative to the site URL. Blank string if no path is specified.
         * @param string|null $scheme  Scheme to give the site URL context. Accepts 'http', 'https', 'login',
         *                             'login_post', 'admin', 'relative' or null.
         */
        return Hook::call('site_url', $url, $path, $scheme);
    }

    public static function sign_in()
    {
        return self::site('dashboard/sign-in');
    }

    public static function sign_up()
    {
        return self::site('dashboard/sign-up');
    }

    public static function reset_password()
    {
        return self::site('dashboard/reset-password');
    }

    public static function install()
    {
        return self::site('dashboard/install');
    }

    public static function installed()
    {
        return self::site('dashboard/installed');
    }

    /**
     * Retrieves the URL to the admin area for a given site.
     *
     * @param  string $path Optional. Path relative to the dashboard URL. Default empty.
     * @return string       Dashboard URL link with optional path appended.
     */
    public static function dashboard(string $path = ''): string
    {
        return self::site(sprintf('dashboard%s', $path));
    }

    /**
     * Extract all URLs from text.
     *
     * @param $text
     * @return array
     */
    public static function extract($text): array
    {
        $urls = [];
        if (is_string($text)) {
            preg_match_all('/\bhttps?:\/\/\S+/', $text, $matches);

            $urls = (array) ( $matches[0] ?? [] );
        }

        return array_unique($urls);
    }

    /**
     * Return url from any path.
     *
     * @param string $path
     * @return string
     */
    public static function fromPath(string $path): string
    {
        return str_replace(dirname(__DIR__), self::site(), $path);
    }

    /**
     * Convert absolute URL of file to path.
     *
     * @param string $url
     * @return string
     */
    public static function toPath(string $url): string
    {
        $relative = ltrim(parse_url($url, PHP_URL_PATH), '/');
        $filepath = sprintf('%s/%s', dirname(__DIR__), $relative);

        if (file_exists($filepath)) {
            return $filepath;
        }
        return '';
    }
}
