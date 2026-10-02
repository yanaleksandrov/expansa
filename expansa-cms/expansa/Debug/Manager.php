<?php

declare(strict_types=1);

namespace Expansa\Debug;

use Closure;
use ErrorException;
use Expansa\Debug\Internal\Page;
use Throwable;

/**
 * Handling of uncaught errors: register() takes over PHP errors, uncaught exceptions and fatal errors,
 * handle() reports an error with a new id and outputs the error page, a JSON response or, in the console,
 * text on STDERR. The details are shown only when configured, e.g. in debug mode.
 *
 * PHP warnings and notices become an ErrorException in strict mode; otherwise they go to the warning
 * callback, once per place and request, and the request goes on. Deprecations always go to the callback. Errors silenced with `@`
 * and levels outside error_reporting() are ignored.
 *
 * Without configuration nothing is reported and the error is output as plain text.
 *
 * @package Expansa\Debug
 */
final class Manager
{
    /**
     * Errors that stop the script and reach only the shutdown function.
     */
    public const int FATAL = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;

    /**
     * Template of the error page, see the variables in documentation/Debug.md.
     *
     * @var string
     */
    private string $view = '';

    /**
     * Show the message, frames and request instead of only the error id.
     *
     * @var bool
     */
    private bool $details = false;

    /**
     * Throw an ErrorException for PHP warnings and notices: debug mode.
     *
     * @var bool
     */
    private bool $strict = false;

    /**
     * Gets the error, its id and the context: writes it to the log.
     *
     * @var Closure|null
     */
    private ?Closure $report = null;

    /**
     * Gets a PHP warning, notice or deprecation that does not stop the request.
     *
     * @var Closure|null
     */
    private ?Closure $warning = null;

    /**
     * Returns the request context: method, URL, user; a Closure value is resolved on its own.
     *
     * @var Closure|null
     */
    private ?Closure $context = null;

    /**
     * Returns whether the response is JSON; the Accept header decides without it.
     *
     * @var Closure|null
     */
    private ?Closure $json = null;

    /**
     * Builds the data of the error page.
     *
     * @var Page
     */
    private Page $page;

    /**
     * Output buffer level at register(): send() drops the buffers above it, e.g. a half-rendered page.
     *
     * @var int|null
     */
    private ?int $bufferLevel = null;

    /**
     * Places of the PHP errors passed to the warning callback, "level:file:line", to pass each once.
     *
     * @var array<string, true>
     */
    private array $warned = [];

    public function __construct(

        /**
         * Write errors to STDERR as text instead of a page.
         */
        private readonly bool $console = PHP_SAPI === 'cli',
    ) {
        $this->page = new Page();
    }

    /**
     * Set the page, the details and the callbacks, a repeated call replaces all of them.
     *
     * @param string       $view     Template of the error page; plain text without it.
     * @param bool         $details  Show the message, frames and request, e.g. in debug mode or to developers.
     * @param bool         $strict   Throw an ErrorException for PHP warnings and notices, e.g. in debug mode.
     * @param Closure|null $report   fn (Throwable $e, string $id, array $context): void, e.g. a log write.
     * @param Closure|null $warning  fn (ErrorException $e): void, for PHP errors that do not stop the request.
     * @param Closure|null $context  fn (): array, the request; a Closure value is resolved on its own.
     * @param Closure|null $json     fn (): bool, whether the response is JSON.
     * @param string[]     $collapse Path prefixes of the frames shown collapsed, e.g. the core and vendor.
     * @return void
     */
    public function configure(
        string $view = '',
        bool $details = false,
        bool $strict = false,
        ?Closure $report = null,
        ?Closure $warning = null,
        ?Closure $context = null,
        ?Closure $json = null,
        array $collapse = [],
    ): void {
        $this->view    = $view;
        $this->details = $details;
        $this->strict  = $strict;
        $this->report  = $report;
        $this->warning = $warning;
        $this->context = $context;
        $this->json    = $json;
        $this->page    = new Page($collapse);
    }

    /**
     * Whether error responses show the message and details, e.g. for an API error envelope built elsewhere.
     *
     * @return bool
     */
    public function hasDetails(): bool
    {
        return $this->details;
    }

    /**
     * Take over PHP errors, uncaught exceptions and fatal errors, once per process.
     * PHP's own output of errors is turned off: handle() outputs them.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->bufferLevel !== null) {
            return;
        }
        // the buffer PHP opens for output_buffering holds the half-rendered page too
        $buffering         = strtolower((string) ini_get('output_buffering'));
        $own               = PHP_SAPI !== 'cli' && ! in_array($buffering, ['', '0', 'off'], true);
        $this->bufferLevel = max(0, ob_get_level() - ($own ? 1 : 0));

        ini_set('display_errors', '0');

        set_error_handler($this->error(...));
        set_exception_handler($this->uncaught(...));

        // registered from a shutdown function, so it runs after the others, e.g. deferred hooks
        register_shutdown_function(fn () => register_shutdown_function($this->shutdown(...)));
    }

    /**
     * Report the error and output the error page.
     *
     * @param Throwable            $e
     * @param array<string, mixed> $context Added to the request context in the report.
     * @return void
     */
    public function handle(Throwable $e, array $context = []): void
    {
        $this->send($e, $this->report($e, $context));
    }

    /**
     * Whether the error stopped the script: out of memory, time limit, a compile error.
     *
     * @param Throwable $e
     * @return bool
     */
    public static function isFatal(Throwable $e): bool
    {
        return $e instanceof ErrorException && ($e->getSeverity() & self::FATAL) !== 0;
    }

    /**
     * Pass the error with a new id and the context to the report callback, e.g. to the log.
     * A failing callback writes both errors to error_log().
     *
     * @param Throwable            $e
     * @param array<string, mixed> $context Added to the request context.
     * @return string Id of the error, shown to the visitor to find it in the log.
     */
    public function report(Throwable $e, array $context = []): string
    {
        $id = bin2hex(random_bytes(4));

        if ($this->report !== null) {
            try {
                ($this->report)($e, $id, Page::sanitize($context) + $this->getContext());
            } catch (Throwable $failure) {
                error_log("Error report failed: $failure" . PHP_EOL . "Error $id: $e");
            }
        }

        return $id;
    }

    /**
     * Output the error: text on STDERR in the console, otherwise JSON or the page with the 500 status.
     * After register() the output buffers started since then are dropped first: a half-rendered page.
     *
     * @param Throwable $e
     * @param string    $id
     * @return void
     */
    public function send(Throwable $e, string $id = ''): void
    {
        if ($this->console) {
            fwrite(STDERR, $this->page->getText($e, $id));
            return;
        }

        $this->discardBuffers();

        $json = $this->isJson();
        $type = match (true) {
            $json               => 'application/json',
            $this->view === ''  => 'text/plain',
            default             => 'text/html',
        };

        if (! headers_sent()) {
            http_response_code(500);
            header("Content-Type: $type; charset=utf-8");
        }

        // a HEAD response has no body; the buffer the router opens to drop it is gone with the others
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') {
            return;
        }

        if ($json) {
            echo json_encode($this->page->getPayload($e, $id, $this->details), Page::ENCODE_FLAGS);
        } elseif ($this->view === '') {
            echo $this->details ? $this->page->getText($e, $id) : "Server Error. Error ID: $id";
        } else {
            $this->renderPage($e, $id);
        }
    }

    /**
     * The request context with secrets masked; a failing value is replaced by the error message.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        if ($this->context === null) {
            return [];
        }

        try {
            $context = ($this->context)();
        } catch (Throwable $e) {
            return ['context' => 'failed: ' . $e->getMessage()];
        }

        foreach ($context as $key => $value) {
            if ($value instanceof Closure) {
                try {
                    $context[$key] = $value();
                } catch (Throwable $e) {
                    $context[$key] = 'failed: ' . $e->getMessage();
                }
            }
        }

        return Page::sanitize($context);
    }

    /**
     * Error handler: throws an ErrorException in strict mode, otherwise passes the error to the warning callback.
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     * @return bool False lets PHP handle the error: silenced or without a callback.
     * @throws ErrorException
     */
    private function error(int $level, string $message, string $file, int $line): bool
    {
        if ((error_reporting() & $level) === 0) {
            return false;
        }

        if ($this->strict && ($level & (E_DEPRECATED | E_USER_DEPRECATED)) === 0) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }

        if ($this->warning === null) {
            return false;
        }

        // the same warning of a loop or a vendor file is passed once per request
        $place = "$level:$file:$line";
        if (isset($this->warned[$place])) {
            return true;
        }
        $this->warned[$place] = true;

        try {
            ($this->warning)(new ErrorException($message, 0, $level, $file, $line));
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Exception handler: handles the exception and, in the console, exits with 255 as PHP itself would.
     *
     * @param Throwable $e
     * @return void
     */
    private function uncaught(Throwable $e): void
    {
        $this->handle($e);

        if ($this->console) {
            exit(255);
        }
    }

    /**
     * Handle the fatal error that stopped the script, if any.
     *
     * @return void
     */
    private function shutdown(): void
    {
        $error = error_get_last();
        if ($error === null || ($error['type'] & self::FATAL) === 0) {
            return;
        }

        // room for the page after "Allowed memory size exhausted", instead of a reserve held by every request
        if (str_starts_with($error['message'], 'Allowed memory size')) {
            ini_set('memory_limit', (string) (memory_get_usage(true) + 8 * 1024 * 1024));
        }

        $this->handle(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
    }

    /**
     * Whether the response is JSON, by the callback or the Accept header.
     *
     * @return bool
     */
    private function isJson(): bool
    {
        if ($this->json === null) {
            return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json');
        }

        try {
            return ($this->json)();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Output the template, or the error as text when the template itself fails.
     *
     * @param Throwable $e
     * @param string    $id
     * @return void
     */
    private function renderPage(Throwable $e, string $id): void
    {
        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__view, array $__data): void {
                extract($__data, EXTR_SKIP);

                include $__view;
            })($this->view, $this->page->getData($e, $id, $this->details ? $this->getContext() : [], $this->details));

            ob_end_flush();
        } catch (Throwable $failure) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            $text = $this->details
                ? $this->page->getText($e, $id) . PHP_EOL . "The error page failed: $failure"
                : "Server Error. Error ID: $id";

            echo '<pre>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }

    /**
     * Drop the output buffers started after register(), except the ones PHP does not let remove.
     *
     * @return void
     */
    private function discardBuffers(): void
    {
        if ($this->bufferLevel === null) {
            return;
        }

        while (ob_get_level() > $this->bufferLevel && (ob_get_status()['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE) !== 0) {
            ob_end_clean();
        }
    }
}
