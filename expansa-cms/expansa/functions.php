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

if (!function_exists('view')) {
    function view(string $view, array $data = []): Expansa\View\View
    {
        return \Expansa\View::make($view, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $location, int $status = 302, string $redirectBy = 'Expansa'): void
    {
        /**
         * Filters the redirect location.
         *
         * @param string $location the path or URL to redirect to
         * @param int    $status   the HTTP response status code to use
         */
        $location = Expansa\Hook::call('expansaRedirectLocation', $location, $status);

        /**
         * Filters the redirect HTTP response status code to use.
         *
         * @param int    $status   the HTTP response status code to use
         * @param string $location the path or URL to redirect to
         */
        $status = Expansa\Hook::call('expansaRedirectStatus', $status, $location);

        if ($location) {
            if ($status < 300 || 399 < $status) {
                new \Expansa\Error('view-redirect', t('HTTP redirect status code must be a redirection code, 3xx.'));

                return;
            }

            /**
             * Filters the X-Redirect-By header.
             *
             * Allows applications to identify themselves when they're doing a redirect.
             *
             * @param string $redirectBy The application doing the redirect.
             * @param int    $status     Status code to use.
             * @param string $location   The path to redirect to.
             */
            $redirectBy = Expansa\Hook::call('expansaRedirectBy', $redirectBy, $status, $location);
            if (is_string($redirectBy)) {
                header("X-Redirect-By: $redirectBy");
            }

            header("Location: $location", true, $status);
        }
    }
}
