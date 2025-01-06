<?php

declare(strict_types=1);

namespace Expansa;

/**
 * Template class.
 */
final class View
{
    /**
     * @param string $location
     * @param int $status
     * @param string $x_redirect_by
     * @return bool|Error
     */
    public static function redirect(string $location, int $status = 302, string $x_redirect_by = 'Expansa'): bool|Error
    {
        /**
         * Filters the redirect location.
         *
         * @param string $location the path or URL to redirect to
         * @param int    $status   the HTTP response status code to use
         */
        $location = Hook::call('expansa_redirect', $location, $status);

        /**
         * Filters the redirect HTTP response status code to use.
         *
         * @param int    $status   the HTTP response status code to use
         * @param string $location the path or URL to redirect to
         */
        $status = Hook::call('expansa_redirect_status', $status, $location);

        if (! $location) {
            return false;
        }

        if ($status < 300 || 399 < $status) {
            return new Error('view-redirect', I18n::_t('HTTP redirect status code must be a redirection code, 3xx.'));
        }

        /**
         * Filters the X-Redirect-By header.
         *
         * Allows applications to identify themselves when they're doing a redirect.
         *
         * @param string $x_redirect_by the application doing the redirect
         * @param int    $status        status code to use
         * @param string $location      the path to redirect to
         */
        $x_redirect_by = Hook::call('x_redirect_by', $x_redirect_by, $status, $location);
        if (is_string($x_redirect_by)) {
            header("X-Redirect-By: {$x_redirect_by}");
        }

        header("Location: {$location}", true, $status);

        return true;
    }

    /**
     * Loads a template part into a template.
     *
     * Using: View::print( 'templates/content', [
     *          'key' => 'value'
     *        ] );.
     *
     * Provides a simple mechanism to overload reusable sections of code.
     * The template is included using require, not require_once, so you
     * may include the same template part multiple times.
     *
     * TODO: include 404 error part if nothing found (possible no needs)
     * TODO: potential problem, - if the same file override several different plugins, which one should I use?
     *
     * @param string $template The path to the view file.
     * @param array  $args     Optional. Additional arguments passed to the template. Default empty array.
     */
    public static function print(string $template, array $args = []): void
    {
        echo self::get($template, $args);
    }

    /**
     * Get template part.
     *
     * @param string $template
     * @param array $args
     * @return string
     */
    public static function get(string $template, array $args = []): string
    {
        $filepath = match (true) {
            str_starts_with($template, EX_PATH) => "{$template}.php",
            Is::dashboard(), Is::install()             => EX_DASHBOARD . "{$template}.php",
            default                                    => "{$template}.php",
        };
        $filepath = Sanitizer::path($filepath);

        /**
         * Override template file.
         *
         * @param string $filepath Path to existing template part.
         */
        $filepath = Hook::call('expansa_view_part', $filepath, $template, $args);

        extract($args, EXTR_SKIP);

//      try {
//          ob_start();
//          require $filepath;
//          return ob_get_clean();
//      } catch ( \Throwable $e ) {
//          ob_end_clean();
//          print_r( $e );
//          throw new \Exception( I18n::_t( 'Failed opening required: :filepath', $filepath ) );
//      }

        ob_start();
        include $filepath;
        return ob_get_clean();
    }
}
