<?php

declare(strict_types=1);

namespace Expansa\Support;

use Random\RandomException;

class Str
{
    protected static array $shakeCache = [];

    protected static array $studlyCache = [];

    protected static array $camelCache = [];

    public static function length(string $string): int
    {
        return mb_strlen($string, 'UTF-8');
    }

    public static function width(string $string): int
    {
        return mb_strwidth($string, 'UTF-8');
    }

    /**
     * @throws RandomException
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function genToken(): string
    {
        $token = match (true) {
            function_exists('random_bytes')                => bin2hex(random_bytes(32)),
            function_exists('openssl_random_pseudo_bytes') => bin2hex(openssl_random_pseudo_bytes(32)),
            default                                                => uniqid(Str::random(32), true),
        };

        return md5($token);
    }

    public static function random(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = crc32($value);

        if (isset(static::$shakeCache[$key][$delimiter])) {
            return static::$shakeCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return self::$shakeCache[$key][$delimiter] = $value;
    }

    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function isLower(string $value): bool
    {
        return self::lower($value) === $value;
    }

    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function isUpper(string $value): bool
    {
        return self::upper($value) === $value;
    }

    public static function lcfirst(string $value): string
    {
        return static::lower(static::substr($value, 0, 1)) . static::substr($value, 1);
    }

    public static function ucfirst(string $value): string
    {
        return static::upper(static::substr($value, 0, 1)) . static::substr($value, 1);
    }

    public static function studly(string $value): string
    {
        $key = crc32($value);

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $words = explode(" ", str_replace(['_', '-'], ' ', $value));

        foreach ($words as $k => $v) {
            $words[$k] = static::ucfirst($v);
        };

        return static::$studlyCache[$key] = implode($words);
    }

    public static function camel(string $value): string
    {
        $key = crc32($value);

        if (isset(static::$camelCache[$key])) {
            return static::$camelCache[$key];
        }

        return static::$camelCache[$key] = static::lcfirst(static::studly($value));
    }

    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public static function isEmpty(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }

    public static function contains(string $haystack, string|array $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = static::lower($haystack);
        }

        if (! is_iterable($needles)) {
            $needles = (array) $needles;
        }

        foreach ($needles as $needle) {
            if ($ignoreCase) {
                $needle = static::lower($needle);
            }

            if (!empty($needle) && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function replaceArray(string $search, array $replace, string $subject): string
    {
        $segments = explode($search, $subject);

        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $result .= (array_shift($replace) ?? $search) . $segment;
        }

        return $result;
    }

    public static function substr($string, int $start, int $length = null): string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    public static function subtract($string, int $start, int $length): string
    {
        return mb_substr($string, 0, $start, 'UTF-8') . mb_substr($string, $start + $length, null, 'UTF-8');
    }

    public static function strpos(string $string, string $needle, int $offset = 0): false|int
    {
        return mb_strpos($string, $needle, $offset, 'UTF-8');
    }

    /**
     * Converts a word to a singular in English, if possible.
     *
     * @param string $word The word that needs to be converted to a singular number.
     * @return string The converted singular word.
     */
    public static function singularize(string $word): string
    {
        $singular = [
            '/(quiz)zes$/i'                                                    => '\\1',
            '/(matr)ices$/i'                                                   => '\\1ix',
            '/(vert|ind)ices$/i'                                               => '\\1ex',
            '/^(ox)en/i'                                                       => '\\1',
            '/(alias|status)es$/i'                                             => '\\1',
            '/([octop|vir])i$/i'                                               => '\\1us',
            '/(cris|ax|test)es$/i'                                             => '\\1is',
            '/(shoe)s$/i'                                                      => '\\1',
            '/(o)es$/i'                                                        => '\\1',
            '/(bus)es$/i'                                                      => '\\1',
            '/([m|l])ice$/i'                                                   => '\\1ouse',
            '/(x|ch|ss|sh)es$/i'                                               => '\\1',
            '/(m)ovies$/i'                                                     => '\\1ovie',
            '/(s)eries$/i'                                                     => '\\1eries',
            '/([^aeiouy]|qu)ies$/i'                                            => '\\1y',
            '/([lr])ves$/i'                                                    => '\\1f',
            '/(tive)s$/i'                                                      => '\\1',
            '/(hive)s$/i'                                                      => '\\1',
            '/([^f])ves$/i'                                                    => '\\1fe',
            '/(^analy)ses$/i'                                                  => '\\1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\\1\\2sis',
            '/([ti])a$/i'                                                      => '\\1um',
            '/(n)ews$/i'                                                       => '\\1ews',
            '/s$/i'                                                            => '',
        ];

        $ignore = ['equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep', 'press', 'sms'];
        if (isset($ignore[strtolower(substr(strtolower($word), -1))])) {
            return $word;
        }

        foreach ($singular as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return $word;
    }
}
