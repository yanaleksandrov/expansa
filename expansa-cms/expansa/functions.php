<?php

declare(strict_types=1);

if (! function_exists('t')) {
    /**
     * Translate with formatting.
     *
     * @param string $string
     * @param mixed  ...$args
     * @return mixed
     */
    function t(string $string, mixed ...$args): string
    {
        if (class_exists('Expansa\I18n')) {
            return Expansa\I18n::_t($string, ...$args);
        }
        return $string;
    }
}

if (! function_exists('t_attr')) {
    /**
     * Translate with formatting and sanitize for use in html attributes.
     *
     * @param string $string
     * @param mixed  ...$args
     * @return mixed
     */
    function t_attr(string $string, mixed ...$args): string
    {
        return Expansa\Safe::attribute(t($string, ...$args));
    }
}

if (! function_exists('root')) {
    /**
     * Get absolute path to file or directory.
     *
     * @param string $string
     * @return mixed
     */
    function root(string $string): string
    {
        return EX_PATH . $string;
    }
}

if (!function_exists('escape')) {
    function escape(mixed $value, bool $doubleEncode = true): string
    {
        return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', $doubleEncode));
    }
}
