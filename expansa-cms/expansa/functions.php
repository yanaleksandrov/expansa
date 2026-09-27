<?php

/**
 * Short global helpers for templates and frequent calls; each one wraps a facade or a class.
 * A function already defined by a plugin or a test is kept: every helper is declared only if missing.
 * Loaded before the PHP version check, so no syntax newer than PHP 8.0 here.
 */

declare(strict_types=1);

if (! function_exists('t')) {
    /**
     * Translate a string, fill its placeholders and render its Markdown; see I18n::translate().
     * Returns the string as is while the translation package is not loaded, e.g. on the requirements page.
     *
     * @param string $string
     * @param mixed  ...$args Values of the placeholders, in order of appearance.
     * @return string HTML.
     */
    function t(string $string, mixed ...$args): string
    {
        if (class_exists('Expansa\Facades\I18n')) {
            return Expansa\Facades\I18n::translate($string, ...$args);
        }

        return $string;
    }
}

if (! function_exists('t_attr')) {
    /**
     * Translate a string for an HTML attribute value: plain text, escaped once; see I18n::translateAttribute().
     *
     * @param string $string
     * @param mixed  ...$args Values of the placeholders, in order of appearance.
     * @return string
     */
    function t_attr(string $string, mixed ...$args): string
    {
        return Expansa\Facades\I18n::translateAttribute($string, ...$args);
    }
}

if (! function_exists('root')) {
    /**
     * Get the absolute path of a file or directory under the installation root, or of the path part of a URL.
     *
     * @param string $string Relative path: `cache/views`.
     * @return string
     */
    function root(string $string): string
    {
        return Expansa\Support\Url::toPath($string);
    }
}

if (! function_exists('escape')) {
    /**
     * Escape a scalar for HTML output and trim it; any other value gives an empty string.
     *
     * @param mixed $value
     * @param bool  $doubleEncode False keeps existing entities like `&amp;` as they are.
     * @return string
     */
    function escape(mixed $value, bool $doubleEncode = true): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return trim(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', $doubleEncode));
    }
}

if (! function_exists('view')) {
    /**
     * Create a view; it renders when cast to a string. See View::create().
     *
     * @param string $view Name of the view or an absolute path without the extension.
     * @param array  $data Variables of the template.
     * @return Expansa\View\View
     */
    function view(string $view, array $data = []): Expansa\View\View
    {
        return Expansa\Facades\View::create($view, $data);
    }
}

if (! function_exists('metrics')) {
    /**
     * Get the time and memory metrics of the request, one instance per request.
     *
     * @return Expansa\Debug\Metric
     */
    function metrics(): Expansa\Debug\Metric
    {
        static $metrics;

        return $metrics ??= new Expansa\Debug\Metric();
    }
}

if (! function_exists('redirect')) {
    /**
     * Redirect to a site path or URL and stop the script; see Redirect::send().
     *
     * @param string $to         Path relative to the site URL or an absolute URL.
     * @param int    $status     3xx status code.
     * @param string $redirectBy X-Redirect-By header value, empty to omit it.
     * @return void
     */
    function redirect(string $to, int $status = 302, string $redirectBy = 'Expansa'): void
    {
        Expansa\Http\Redirect::send(url($to), $status, $redirectBy);
    }
}

if (! function_exists('tree')) {
    /**
     * Render a tree registered by Tree::attach(): menu items, comments, taxonomies.
     *
     * @param string   $name     Name of the tree.
     * @param callable $function Gets the parsed items and the tree, prints the markup.
     * @return string The printed markup.
     */
    function tree(string $name, callable $function): string
    {
        ob_start();
        Expansa\Builders\Tree::view($name, $function);

        return (string) ob_get_clean();
    }
}

if (! function_exists('form')) {
    /**
     * Render a form: the file that registers it is loaded once, then Form::render() builds the markup.
     *
     * @param string $uid  Form id.
     * @param string $path File registering the form, skipped if missing.
     * @return string Markup, empty for an unknown form.
     */
    function form(string $uid, string $path): string
    {
        if (is_file($path)) {
            require_once $path;
        }

        return Expansa\Facades\Form::render($uid);
    }
}

if (! function_exists('url')) {
    /**
     * Get the site URL with a path; see Url::site().
     *
     * @param string $slug Path relative to the site URL.
     * @return string
     */
    function url(string $slug = ''): string
    {
        return Expansa\Support\Url::site($slug);
    }
}

if (! function_exists('session')) {
    /**
     * Get the session of the request with the driver of Session::configure().
     *
     * @return Expansa\Session\Contracts\Session&Expansa\Session\Contracts\Lifecycle
     */
    function session(): Expansa\Session\Contracts\Session
    {
        return Expansa\Facades\Session::driver();
    }
}

if (! function_exists('error')) {
    /**
     * Add an error with a code, or append a message to the error with this code.
     *
     * @param string          $code    Error code: `user-signin`.
     * @param string|string[] $message
     * @return Expansa\Debug\Error
     */
    function error(string $code, string|array $message = ''): Expansa\Debug\Error
    {
        return new Expansa\Debug\Error($code, $message);
    }
}

if (! function_exists('value')) {
    /**
     * Resolve a value: a Closure is called with the arguments, anything else is returned as is.
     * Lets a caller pass an expensive default lazily: `value($default)`.
     *
     * @param mixed $value
     * @param mixed ...$args Arguments of the Closure.
     * @return mixed
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
