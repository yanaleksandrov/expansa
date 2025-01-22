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
    function redirect(string $to, int $status = 302, string $redirectBy = 'Expansa'): null
    {
        /**
         * Filters the redirect location.
         *
         * @param string $to the path or URL to redirect to
         * @param int    $status   the HTTP response status code to use
         */
        $to = Expansa\Hook::call('expansaRedirectLocation', $to, $status);

        /**
         * Filters the redirect HTTP response status code to use.
         *
         * @param int    $status   the HTTP response status code to use
         * @param string $to the path or URL to redirect to
         */
        $status = Expansa\Hook::call('expansaRedirectStatus', $status, $to);

        if ($to) {
            if ($status < 300 || 399 < $status) {
                new \Expansa\Error('view-redirect', t('HTTP redirect status code must be a redirection code, 3xx.'));

                return null;
            }

            /**
             * Filters the X-Redirect-By header.
             *
             * Allows applications to identify themselves when they're doing a redirect.
             *
             * @param string $redirectBy The application doing the redirect.
             * @param int    $status     Status code to use.
             * @param string $to   The path to redirect to.
             */
            $redirectBy = Expansa\Hook::call('expansaRedirectBy', $redirectBy, $status, $to);
            if (is_string($redirectBy)) {
                header("X-Redirect-By: $redirectBy");
            }

            header("Location: $to", true, $status);

            return exit;
        }
        return null;
    }
}
