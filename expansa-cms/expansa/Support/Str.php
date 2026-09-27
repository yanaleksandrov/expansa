<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * UTF-8 string helpers: random strings, case conversion, search, English singular.
 *
 * @package Expansa\Support
 */
final class Str
{
    private const string RANDOM_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Uncountable words singularize() returns as they are, stored as keys for isset().
     */
    private const array UNCOUNTABLE = [
        // materials and substances
        'water'       => true,
    'air'         => true,
    'sand'        => true,
    'sugar'       => true,
    'salt'        => true,
    'rice'        => true,
        'flour'       => true,
    'oil'         => true,
    'butter'      => true,
    'cheese'      => true,
    'milk'        => true,
    'coffee'      => true,
        'tea'         => true,
    'honey'       => true,
    'meat'        => true,
    'fish'        => true,
    'sheep'       => true,

        // abstract concepts and states
        'information' => true,
    'advice'      => true,
    'knowledge'   => true,
    'news'        => true,
    'progress'    => true,
        'work'        => true,
    'homework'    => true,
    'luck'        => true,
    'happiness'   => true,
    'freedom'     => true,
        'education'   => true,
    'music'       => true,
    'poetry'      => true,
    'patience'    => true,
    'traffic'     => true,
        'press'       => true,
    'sms'         => true,

        // money and economic concepts
        'money'       => true,
    'currency'    => true,
    'wealth'      => true,
    'commerce'    => true,
    'trade'       => true,

        // food and drinks
        'bread'       => true,
    'food'        => true,
    'juice'       => true,
    'wine'        => true,
    'beer'        => true,

        // languages and academic subjects
        'english'     => true,
    'french'      => true,
    'mathematics' => true,
    'physics'     => true,
    'chemistry'   => true,

        // others
        'furniture'   => true,
    'equipment'   => true,
    'species'     => true,
    'series'      => true,
    'software'    => true,
        'hardware'    => true,
    'clothing'    => true,
    'luggage'     => true,
    'weather'     => true,
    'machinery'   => true,
    ];

    /**
     * Plural endings and their singular replacements, the first match wins.
     */
    private const array SINGULAR = [
        '/(quiz)zes$/i'                                                    => '\1',
        '/(matr)ices$/i'                                                   => '\1ix',
        '/(vert|ind)ices$/i'                                               => '\1ex',
        '/^(ox)en/i'                                                       => '\1',
        '/(alias|status)es$/i'                                             => '\1',
        '/([octop|vir])i$/i'                                               => '\1us',
        '/(cris|ax|test)es$/i'                                             => '\1is',
        '/(shoe)s$/i'                                                      => '\1',
        '/(o)es$/i'                                                        => '\1',
        '/(bus)es$/i'                                                      => '\1',
        '/([m|l])ice$/i'                                                   => '\1ouse',
        '/(x|ch|ss|sh)es$/i'                                               => '\1',
        '/(m)ovies$/i'                                                     => '\1ovie',
        '/(s)eries$/i'                                                     => '\1eries',
        '/([^aeiouy]|qu)ies$/i'                                            => '\1y',
        '/([lr])ves$/i'                                                    => '\1f',
        '/(tive)s$/i'                                                      => '\1',
        '/(hive)s$/i'                                                      => '\1',
        '/([^f])ves$/i'                                                    => '\1fe',
        '/(^analy)ses$/i'                                                  => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
        '/([ti])a$/i'                                                      => '\1um',
        '/(n)ews$/i'                                                       => '\1ews',
        '/s$/i'                                                            => '',
    ];

    /**
     * Get the number of characters of a UTF-8 string.
     *
     * @param string $string
     * @return int
     */
    public static function length(string $string): int
    {
        return mb_strlen($string, 'UTF-8');
    }

    /**
     * Get the display width of a UTF-8 string: wide characters count as two.
     *
     * @param string $string
     * @return int
     */
    public static function width(string $string): int
    {
        return mb_strwidth($string, 'UTF-8');
    }

    /**
     * Generate a random UUID version 4, e.g. "3f2b8c1e-9d4a-4f6b-8e2c-7a1d5b9c0e3f".
     *
     * @return string
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate a cryptographically secure token of 32 hex characters (128 bits).
     *
     * @return string
     */
    public static function token(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a cryptographically secure random alphanumeric string, for tokens and API keys.
     *
     * @param int $length Number of characters; 32 characters give about 190 bits of entropy.
     * @return string
     */
    public static function random(int $length): string
    {
        $max = strlen(self::RANDOM_CHARS) - 1;

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= self::RANDOM_CHARS[random_int(0, $max)];
        }

        return $string;
    }

    /**
     * Convert to snake case: `postTitle` → `post_title`. Results are cached for the request.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        static $cache = [];

        if (isset($cache[$delimiter][$value])) {
            return $cache[$delimiter][$value];
        }

        $result = $value;
        if (! ctype_lower($value)) {
            $result = preg_replace('/\s+/u', '', ucwords($value));
            $result = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $result), 'UTF-8');
        }

        return $cache[$delimiter][$value] = $result;
    }

    /**
     * Convert to lower case.
     *
     * @param string $value
     * @return string
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Check if a string has no upper case characters.
     *
     * @param string $value
     * @return bool
     */
    public static function isLower(string $value): bool
    {
        return self::lower($value) === $value;
    }

    /**
     * Convert to upper case.
     *
     * @param string $value
     * @return string
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Check if a string has no lower case characters.
     *
     * @param string $value
     * @return bool
     */
    public static function isUpper(string $value): bool
    {
        return self::upper($value) === $value;
    }

    /**
     * Make the first character lower case.
     *
     * @param string $value
     * @return string
     */
    public static function lcfirst(string $value): string
    {
        return self::lower(self::substr($value, 0, 1)) . self::substr($value, 1);
    }

    /**
     * Make the first character upper case.
     *
     * @param string $value
     * @return string
     */
    public static function ucfirst(string $value): string
    {
        return self::upper(self::substr($value, 0, 1)) . self::substr($value, 1);
    }

    /**
     * Convert to studly case: `post_title` or `post-title` → `PostTitle`. Results are cached for the request.
     *
     * @param string $value
     * @return string
     */
    public static function studly(string $value): string
    {
        static $cache = [];

        return $cache[$value] ??= str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value)));
    }

    /**
     * Convert to title case: every word starts with an upper case character.
     *
     * @param string $value
     * @return string
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert to camel case: `post_title` or `post-title` → `postTitle`. Results are cached for the request.
     *
     * @param string $value
     * @return string
     */
    public static function camel(string $value): string
    {
        static $cache = [];

        return $cache[$value] ??= lcfirst(self::studly($value));
    }

    /**
     * Check if a value is null or a blank string; booleans and arrays are never empty.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isEmpty(mixed $value): bool
    {
        return $value === null || (! is_bool($value) && ! is_array($value) && trim((string) $value) === '');
    }

    /**
     * Check if a string contains any of the needles; empty needles never match.
     *
     * @param string          $haystack
     * @param string|string[] $needles
     * @param bool            $ignoreCase
     * @return bool
     */
    public static function contains(string $haystack, string|array $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = self::lower($haystack);
        }

        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $ignoreCase ? self::lower($needle) : $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace every occurrence of a search string with the next value of a list, in order.
     * Occurrences beyond the list keep the search string.
     *
     * @param string   $search
     * @param string[] $replace
     * @param string   $subject
     * @return string
     */
    public static function replaceArray(string $search, array $replace, string $subject): string
    {
        $segments = explode($search, $subject);
        $result   = array_shift($segments);

        foreach ($segments as $segment) {
            $result .= (array_shift($replace) ?? $search) . $segment;
        }

        return $result;
    }

    /**
     * Get a part of a UTF-8 string.
     *
     * @param string   $string
     * @param int      $start
     * @param int|null $length
     * @return string
     */
    public static function substr(string $string, int $start, ?int $length = null): string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Cut a part out of a UTF-8 string, returning the rest.
     *
     * @param string $string
     * @param int    $start
     * @param int    $length
     * @return string
     */
    public static function subtract(string $string, int $start, int $length): string
    {
        return mb_substr($string, 0, $start, 'UTF-8') . mb_substr($string, $start + $length, null, 'UTF-8');
    }

    /**
     * Find the character position of a needle in a UTF-8 string.
     *
     * @param string $string
     * @param string $needle
     * @param int    $offset
     * @return int|false
     */
    public static function strpos(string $string, string $needle, int $offset = 0): int|false
    {
        return mb_strpos($string, $needle, $offset, 'UTF-8');
    }

    /**
     * Convert an English word to the singular, if possible.
     *
     * @param string $word
     * @return string
     */
    public static function singularize(string $word): string
    {
        if (isset(self::UNCOUNTABLE[strtolower($word)])) {
            return $word;
        }

        foreach (self::SINGULAR as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return $word;
    }

    /**
     * Find the unique http(s) URLs in a text.
     *
     * @param string $text
     * @return string[]
     */
    public static function extractUrls(string $text): array
    {
        preg_match_all('/\bhttps?:\/\/\S+/', $text, $matches);

        return array_values(array_unique($matches[0]));
    }
}
