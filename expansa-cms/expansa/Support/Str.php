<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * String helpers: random strings, case conversion, English singular.
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
        'water' => true, 'air' => true, 'sand' => true, 'sugar' => true, 'salt' => true, 'rice' => true,
        'flour' => true, 'oil' => true, 'butter' => true, 'cheese' => true, 'milk' => true, 'coffee' => true,
        'tea' => true, 'honey' => true, 'meat' => true, 'fish' => true, 'sheep' => true,

        // abstract concepts and states
        'information' => true, 'advice' => true, 'knowledge' => true, 'news' => true, 'progress' => true,
        'work' => true, 'homework' => true, 'luck' => true, 'happiness' => true, 'freedom' => true,
        'education' => true, 'music' => true, 'poetry' => true, 'patience' => true, 'traffic' => true,
        'press' => true, 'sms' => true,

        // money and economic concepts
        'money' => true, 'currency' => true, 'wealth' => true, 'commerce' => true, 'trade' => true,

        // food and drinks
        'bread' => true, 'food' => true, 'juice' => true, 'wine' => true, 'beer' => true,

        // languages and academic subjects
        'english' => true, 'french' => true, 'mathematics' => true, 'physics' => true, 'chemistry' => true,

        // others
        'furniture' => true, 'equipment' => true, 'species' => true, 'series' => true, 'software' => true,
        'hardware' => true, 'clothing' => true, 'luggage' => true, 'weather' => true, 'machinery' => true,
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
     * Convert to camel case: `post_title` or `post-title` → `postTitle`. Results are cached for the request.
     *
     * @param string $value
     * @return string
     */
    public static function camel(string $value): string
    {
        static $cache = [];

        return $cache[$value] ??= lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value))));
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
