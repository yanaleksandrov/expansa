<?php

declare(strict_types=1);

namespace Expansa\Asset;

class Sanitizer
{
    /**
     * Sanitize ID of asset.
     *
     * @param string $uid
     * @return string
     */
    public static function id(string $uid): string
    {
        $uid = str_replace(['_', '.', ',', ' '], '-', $uid);

        return trim(preg_replace('/\W-/', '', $uid));
    }

    /**
     * Sanitize key of asset.
     *
     * @param string $key
     * @return string
     */
    public static function key(string $key): string
    {
        $key = str_replace(['_', '-'], ' ', $key);

        return lcfirst(str_replace(' ', '', ucwords($key)));
    }

    /**
     * Sanitize asset url.
     *
     * @param string $url
     * @return string
     */
    public static function url(string $url): string
    {
        // remove leading and trailing whitespace
        $url = trim($url);

        // normalize slashes
        $url = str_replace(';//', '://', $url);

        // remove characters other than letters, numbers, hyphens, underscores, dots, and slashes
        $url = preg_replace('/[^a-zA-Z0-9-_.:\/?]/', '', $url);

        // remove double and triple slashes but not :// after http protocol
        return preg_replace('#([^:])//+#', '\1/', $url);
    }

    /**
     * Sanitize array.
     *
     * @param mixed $data
     * @return array
     */
    public static function array(mixed $data): array
    {
        return is_array($data) ? $data : [];
    }
}
