<?php

declare(strict_types=1);

namespace Expansa\Http;

use Expansa\Facades\Hook;
use InvalidArgumentException;

/**
 * @package Expansa\Http
 */
final class Redirect
{
    private array $values = [];

    private ?string $to;

    private string $redirectBy = 'Expansa';

    private int $status = 302;

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

    // Метод для перенаправления назад
    public function back(): self
    {
        $this->to = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->status = 302;

        // Немедленное перенаправление
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

        /**
         * Filters the redirect location.
         *
         * @param string $to     The path or URL to redirect to.
         * @param int    $status The HTTP response status code to use.
         */
        $this->to = Hook::call('expansaRedirectLocation', $to, $status);

        /**
         * Filters the redirect HTTP response status code to use.
         *
         * @param int    $status The HTTP response status code to use.
         * @param string $to     The path or URL to redirect to.
         */
        $this->status = Hook::call('expansaRedirectStatus', $status, $to);

        /**
         * Filters the X-Redirect-By header, allows applications to identify themselves when they're doing a redirect.
         *
         * @param string $redirectBy The application doing the redirect.
         * @param int    $status     Status code to use.
         * @param string $to         The path to redirect to.
         */
        $this->redirectBy = Hook::call('expansaRedirectBy', $redirectBy, $status, $to);

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
