<?php

declare(strict_types=1);

namespace Expansa\Http;

use Expansa\Http\Contracts\Request as RequestContract;
use Expansa\Http\Contracts\Response as ResponseContract;
use Expansa\Http\Enums\Notice;
use Stringable;

/**
 * Outgoing HTTP response: status, headers, cookies and body, sent with send().
 *
 * An API response can instead ask `$ajax` (src/js/youla-ajax.js) to run actions on the page, in order:
 *
 * ```php
 * return $response->notify(t('Token revoked'))->remove("#token-$id");
 * ```
 *
 * Each action is a fragment `{target, action[:delay]: value}`; page-wide ones (notify, redirect, reload,
 * changeUrl) have no target. `$delay` runs an action that many milliseconds later. These are page
 * actions of `$ajax`, not HTTP: `redirect()` here is not a `Location` header, see Redirect for that.
 *
 * The actions return a new response and leave this one as it was, so chain them; the other
 * methods (json(), setHeader(), setCookie()) change the response itself.
 *
 * @package Expansa\Http
 */
final class Response implements ResponseContract
{
    /**
     * Charset appended to the Content-Type header.
     */
    private const string CHARSET = 'utf-8';

    /**
     * Set-Cookie header values.
     *
     * @var array<string|Stringable>
     */
    public private(set) array $cookies = [];

    /**
     * Actions for `$ajax` (src/js/youla-ajax.js) in the order they run; the body is `{"data": [...]}` of them.
     *
     * @var list<array<string, mixed>>
     */
    public private(set) array $fragments = [];

    public function __construct(

        /**
         * Response body.
         */
        public string $content = '',

        /**
         * HTTP status code.
         */
        public int $statusCode = 200,

        /**
         * Headers, name => value.
         *
         * @var array<string, string>
         */
        public array $headers = [],
    ) {}

    /**
     * Add a cookie, sent as a Set-Cookie header.
     *
     * @param string|Stringable $cookie The header value: `id=1; Path=/; HttpOnly` or an object that renders it.
     * @return static
     */
    public function setCookie(string|Stringable $cookie): static
    {
        $this->cookies[] = $cookie;

        return $this;
    }

    /**
     * Forget the cookies added so far.
     *
     * @return void
     */
    public function flushCookies(): void
    {
        $this->cookies = [];
    }

    /**
     * Set a header, replacing the one with the same name; fluent alternative to $headers.
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Set the body to the JSON of the data along with the Content-Type header.
     *
     * @param array<array-key, mixed> $data
     * @param int                     $statusCode
     * @param array<string, string>   $headers    Added to the current headers.
     * @return static
     */
    public function json(array $data, int $statusCode = 200, array $headers = []): static
    {
        $this->content    = (string) json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->statusCode = $statusCode;
        $this->headers    = [...$this->headers, 'Content-Type' => 'application/json', ...$headers];

        return $this;
    }

    /**
     * Show a notification.
     *
     * @param string   $message
     * @param Notice   $type
     * @param int|null $duration Milliseconds on screen; null is the default, 0 keeps it until closed.
     * @param int      $delay
     * @return static
     */
    public function notify(string $message, Notice $type = Notice::Info, ?int $duration = null, int $delay = 0): static
    {
        $value = match (true) {
            $duration !== null     => [$message, $type->value, $duration],
            $type !== Notice::Info => [$message, $type->value],
            default                => $message,
        };

        return $this->add(null, 'notify', $value, $delay);
    }

    /**
     * Open another address.
     *
     * @param string $url
     * @param int    $delay
     * @return static
     */
    public function redirect(string $url, int $delay = 0): static
    {
        return $this->add(null, 'redirect', $url, $delay);
    }

    /**
     * Reload the page.
     *
     * @param int $delay
     * @return static
     */
    public function reload(int $delay = 0): static
    {
        return $this->add(null, 'reload', true, $delay);
    }

    /**
     * Change the address in the browser without loading it, history.pushState().
     *
     * @param string $url
     * @param int    $delay
     * @return static
     */
    public function changeUrl(string $url, int $delay = 0): static
    {
        return $this->add(null, 'changeURL', $url, $delay);
    }

    /**
     * Scroll the page smoothly to the top of the target.
     *
     * @param string $target CSS selector.
     * @param int    $delay
     * @return static
     */
    public function scrollTo(string $target, int $delay = 0): static
    {
        return $this->add($target, 'scrollTo', true, $delay);
    }

    /**
     * Scroll the target into view.
     *
     * @param string                          $target  CSS selector.
     * @param bool|array<string, string|bool> $options Element.scrollIntoView() options, e.g. ['block' => 'center'].
     * @param int                             $delay
     * @return static
     */
    public function scrollIntoView(string $target, bool|array $options = true, int $delay = 0): static
    {
        return $this->add($target, 'scrollIntoView', $options, $delay);
    }

    /**
     * Set the value of fields; they get an `input` event, so the state bound to them follows.
     *
     * @param string $target CSS selector.
     * @param string $value
     * @param int    $delay
     * @return static
     */
    public function value(string $target, string $value, int $delay = 0): static
    {
        return $this->add($target, 'value', $value, $delay);
    }

    /**
     * Replace the content of the target.
     *
     * @param string $target CSS selector.
     * @param string $html
     * @param int    $delay
     * @return static
     */
    public function update(string $target, string $html, int $delay = 0): static
    {
        return $this->add($target, 'update', $html, $delay);
    }

    /**
     * Replace the target itself.
     *
     * @param string $target CSS selector.
     * @param string $html
     * @param int    $delay
     * @return static
     */
    public function replace(string $target, string $html, int $delay = 0): static
    {
        return $this->add($target, 'replace', $html, $delay);
    }

    /**
     * Remove the target from the page.
     *
     * @param string $target CSS selector.
     * @param int    $delay
     * @return static
     */
    public function remove(string $target, int $delay = 0): static
    {
        return $this->add($target, 'remove', true, $delay);
    }

    /**
     * Insert HTML before the target.
     *
     * @param string $target CSS selector.
     * @param string $html
     * @param int    $delay
     * @return static
     */
    public function before(string $target, string $html, int $delay = 0): static
    {
        return $this->add($target, 'before', $html, $delay);
    }

    /**
     * Insert HTML as the first child of the target.
     *
     * @param string $target CSS selector.
     * @param string $html
     * @param int    $delay
     * @return static
     */
    public function prepend(string $target, string $html, int $delay = 0): static
    {
        return $this->add($target, 'prepend', $html, $delay);
    }

    /**
     * Insert HTML as the last child of the target.
     *
     * @param string $target CSS selector.
     * @param string $html
     * @param int    $delay
     * @return static
     */
    public function append(string $target, string $html, int $delay = 0): static
    {
        return $this->add($target, 'append', $html, $delay);
    }

    /**
     * Insert HTML after the target.
     *
     * @param string $target CSS selector.
     * @param string $html
     * @param int    $delay
     * @return static
     */
    public function after(string $target, string $html, int $delay = 0): static
    {
        return $this->add($target, 'after', $html, $delay);
    }

    /**
     * Add classes to the target.
     *
     * @param string          $target CSS selector.
     * @param string|string[] $class  One class or several.
     * @param int             $delay
     * @return static
     */
    public function addClass(string $target, string|array $class, int $delay = 0): static
    {
        return $this->add($target, 'classList.add', $class, $delay);
    }

    /**
     * Remove classes from the target.
     *
     * @param string          $target CSS selector.
     * @param string|string[] $class  One class or several.
     * @param int             $delay
     * @return static
     */
    public function removeClass(string $target, string|array $class, int $delay = 0): static
    {
        return $this->add($target, 'classList.remove', $class, $delay);
    }

    /**
     * Set an attribute of the target.
     *
     * @param string $target CSS selector.
     * @param string $name
     * @param string $value
     * @param int    $delay
     * @return static
     */
    public function setAttribute(string $target, string $name, string $value = '', int $delay = 0): static
    {
        return $this->add($target, 'setAttribute', [$name, $value], $delay);
    }

    /**
     * Remove an attribute of the target.
     *
     * @param string $target CSS selector.
     * @param string $name
     * @param int    $delay
     * @return static
     */
    public function removeAttribute(string $target, string $name, int $delay = 0): static
    {
        return $this->add($target, 'removeAttribute', $name, $delay);
    }

    /**
     * Adjust the response to the request: a HEAD response has no body.
     *
     * @param RequestContract $request
     * @return static
     */
    public function prepare(RequestContract $request): static
    {
        if ($request->method === 'HEAD') {
            $this->content = '';
        }

        return $this;
    }

    /**
     * Send the headers and the body, then finish the request so the script can go on without the client waiting.
     *
     * @return static
     */
    public function send(): static
    {
        if (! headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                if (strcasecmp($name, 'Content-Type') === 0) {
                    $value .= '; charset=' . self::CHARSET;
                }
                header($name . ': ' . $value, false);
            }

            foreach ($this->cookies as $cookie) {
                header('Set-Cookie: ' . $cookie, false);
            }
        }

        echo $this->content;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            self::closeOutputBuffers(0, flush: true);
        }

        return $this;
    }

    /**
     * Whether the status is a redirect (201 or 3xx with a location), to the location if given.
     *
     * @param string|null $location
     * @return bool
     */
    public function isRedirect(?string $location = null): bool
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308], true)
            && ($location === null || $location === ($this->headers['Location'] ?? null));
    }

    /**
     * Copy the response with one more fragment and the body rendered anew. A copy, so a response
     * passed around keeps its fragments: `$invalid = $response->notify(...)` does not add to the others.
     *
     * @param string|null $target CSS selector, null for a page-wide action.
     * @param string      $action Action name in youla-ajax.js.
     * @param mixed       $value
     * @param int         $delay  Milliseconds before the action runs.
     * @return static
     */
    private function add(?string $target, string $action, mixed $value, int $delay): static
    {
        $copy = clone $this;

        $copy->fragments[] = ($target === null ? [] : ['target' => $target]) + [($delay > 0 ? "$action:$delay" : $action) => $value];

        return $copy->json(['data' => $copy->fragments], $copy->statusCode);
    }

    /**
     * Close the output buffers above the level that allow it, flushing or discarding their content.
     *
     * @param int  $targetLevel Buffer level to stop at, 0 closes all.
     * @param bool $flush       Send the content instead of discarding it.
     * @return void
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $flags  = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);
        $status = ob_get_status(true);

        for ($level = count($status) - 1; $level >= $targetLevel; $level--) {
            if (($status[$level]['flags'] & $flags) !== $flags) {
                break;
            }
            $flush ? ob_end_flush() : ob_end_clean();
        }

        if ($flush) {
            flush();
        }
    }
}
