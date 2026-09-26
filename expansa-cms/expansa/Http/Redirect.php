<?php

declare(strict_types=1);

namespace Expansa\Http;

use Closure;
use InvalidArgumentException;

/**
 * HTTP redirect: sends the Location and X-Redirect-By headers and stops the script.
 * Location, status and X-Redirect-By pass through the filters set in configure().
 *
 * @package Expansa\Http
 */
final class Redirect
{
    /**
     * Filter of the location, gets the location and status.
     */
    private static ?Closure $locationFilter = null;

    /**
     * Filter of the status code, gets the status and location.
     */
    private static ?Closure $statusFilter = null;

    /**
     * Filter of the X-Redirect-By header, gets the value, status and location.
     */
    private static ?Closure $redirectByFilter = null;

    private array $values = [];

    private ?string $to = null;

    private string $redirectBy = 'Expansa';

    private int $status = 302;

    /**
     * Set the filters, a repeated call replaces all of them.
     *
     * @param Closure|null $location   fn (string $to, int $status): string
     * @param Closure|null $status     fn (int $status, string $to): int
     * @param Closure|null $redirectBy fn (string $redirectBy, int $status, string $to): string
     * @return void
     */
    public static function configure(?Closure $location = null, ?Closure $status = null, ?Closure $redirectBy = null): void
    {
        self::$locationFilter   = $location;
        self::$statusFilter     = $status;
        self::$redirectByFilter = $redirectBy;
    }

    public function await(int $seconds = 7): void
    {
        $title = t('Redirecting to :link', $this->to);
        $text  = t('Redirecting to [:url](:url) after **:seconds** seconds.', $this->to, $this->to, $seconds);
        $meta  = sprintf('%d;url=%s', $seconds, htmlspecialchars($this->to, ENT_QUOTES, 'UTF-8'));

        echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8' />
    <meta http-equiv='refresh' content='$meta' />
    <title>$title</title>
</head>
<body
    style='display: flex; place-items: center; place-content: center; height: 100dvh; margin: 0'
    onload='var t=$seconds; setInterval(() => document.querySelector(`p strong`).innerText = t--, 1000)'
>
    <p>$text</p>
</body>
</html>";
    }

    /**
     * Redirect to the referer, at once if no values are waiting for with().
     *
     * @return self
     */
    public function back(): self
    {
        $this->to     = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->status = 302;

        if (empty($this->values)) {
            $this->redirect($this->to, $this->status);
        }

        return $this;
    }

    public function with(string $key, array $values): void
    {
        $_SESSION[$this->redirectBy][$key] = $values;

        $this->redirect($this->to, $this->status);
    }

    public function redirect(string $to, int $status = 302, string $redirectBy = 'Expansa'): self
    {
        $to = url($to);

        $this->to         = self::$locationFilter ? (self::$locationFilter)($to, $status) : $to;
        $this->status     = self::$statusFilter ? (self::$statusFilter)($status, $to) : $status;
        $this->redirectBy = self::$redirectByFilter ? (self::$redirectByFilter)($redirectBy, $status, $to) : $redirectBy;

        if ($this->to) {
            if ($this->status < 300 || 399 < $this->status) {
                throw new InvalidArgumentException(t('HTTP redirect status code must be a redirection code, 3xx.'));
            }

            if (!empty($this->redirectBy)) {
                header("X-Redirect-By: $this->redirectBy");
            }

            header("Location: $this->to", true, $this->status);
            exit;
        }

        return $this;
    }
}
