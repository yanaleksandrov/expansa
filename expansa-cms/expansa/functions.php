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
