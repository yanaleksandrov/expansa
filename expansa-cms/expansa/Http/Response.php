<?php

declare(strict_types=1);

namespace Expansa\Http;

use Expansa\Http\Contracts\Request as RequestContract;
use Expansa\Http\Contracts\Response as ResponseContract;
use Stringable;

class Response implements ResponseContract
{
    protected string $version = '1.1';

    protected string $charset = 'utf-8';

    /**
     * Set-Cookie header values.
     *
     * @var array<string|Stringable>
     */
    public protected(set) array $cookies = [];

    protected array $headers = [];

    protected int $statusCode = 200;

    protected string $statusText = '';

    protected ?string $content = null;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->setContent($content)
             ->setStatusCode($statusCode)
             ->setHeaders($headers);
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function code(int $statusCode): static
    {
        return $this->setStatusCode($statusCode);
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function header(string $name, string $value): static
    {
        return $this->setHeader($name, $value);
    }

    public function setHeaders(array $headers = []): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function withHeaders(array $headers = []): static
    {
        return $this->setHeaders($headers);
    }

    public function headers(array $headers = []): static
    {
        return $this->setHeaders($headers);
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

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content ?? '';
    }

    public function content(mixed $content = null): static|string|null
    {
        if (is_null($content)) {
            return $this->getContent();
        }

        return $this->setContent($content);
    }

    public function json(array $data, int $statusCode = 200, array $headers = []): static
    {
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);

        $this->header('Content-Type', 'application/json');

        return $this->setStatusCode($statusCode)->setHeaders($headers);
    }

    public function prepare(RequestContract $request): static
    {
        if ($request->isMethod('HEAD')) {
            $this->setContent(null);
        }

        return $this;
    }

    public function send(): static
    {
        $this->sendHeaders()->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }

        return $this;
    }

    protected function sendHeaders(): static
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        // headers
        foreach ($this->headers as $name => $value) {
            if (strtolower($name) == 'content-type') {
                header($name . ': ' . $value . '; charset=' . $this->charset, false, $this->statusCode);
            } else {
                header($name . ': ' . $value, false, $this->statusCode);
            }
        }

        foreach ($this->cookies as $cookie) {
            header("Set-Cookie: " . (string) $cookie, false, $this->statusCode);
        }

        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);

        return $this;
    }

    protected function sendContent(): static
    {
        echo $this->getContent();

        return $this;
    }

    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level  = count($status);
        $flags  = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
                flush();
            } else {
                ob_end_clean();
            }
        }
    }

    public function isRedirect(?string $location = null): bool
    {
        return in_array(
            $this->statusCode,
            [201, 301, 302, 303, 307, 308]
        ) && (null === $location || $location == ($this->headers['Location'] ?? null));
    }
}
