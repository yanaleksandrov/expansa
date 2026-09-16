<?php

declare(strict_types=1);

namespace Expansa\Translation;

/**
 * The Locale class is responsible for retrieving and storing the user's locale
 * based on the HTTP `Accept-Language` header. It provides a method to fetch the
 * locale in a standardized format, replacing underscores with hyphens.
 *
 * @package Expansa
 */
class Locale
{
    /**
     * Get local from HTTP.
     *
     * @param string $default
     * @return string
     */
    protected function getLocale(string $default = 'en-US'): string
    {
        // Scoped to this method only - no other method reads or resets the detected locale.
        static $locale;

        if (! isset($locale) && function_exists('locale_accept_from_http')) {
            $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? $default);
        }
        return str_replace('_', '-', $locale ?? $default);
    }
}
