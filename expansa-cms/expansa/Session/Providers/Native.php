<?php

declare(strict_types=1);

namespace Expansa\Session\Providers;

use Expansa\Session\Contracts\Flash;
use Expansa\Session\Contracts\Lifecycle;
use Expansa\Session\Contracts\Session;
use Expansa\Session\Exceptions\AlreadyStarted;
use Expansa\Session\Exceptions\HeadersSent;
use Expansa\Session\Exceptions\NotStarted;
use Expansa\Session\Internal\Flash as SessionFlash;
use RuntimeException;

/**
 * PHP native session: data lives in $_SESSION after start(), before it in memory.
 *
 * @package Expansa\Session
 */
final class Native implements Session, Lifecycle
{
    public private(set) Flash $flash;

    public bool $started {
        get => session_status() === PHP_SESSION_ACTIVE;
    }

    public string $id {
        get => (string) session_id();
    }

    public string $name {
        get => (string) session_name();
    }

    /**
     * Session data, a reference to $_SESSION after start().
     *
     * @var array<string, mixed>
     */
    private array $storage = [];

    /**
     * Cookie and session settings; `cache_limiter` is one of public, private_no_expire, private,
     * nocache or '' to send no cache headers.
     *
     * @var array<string, mixed>
     */
    private array $options = [
        'id'            => null,
        'name'          => 'app',
        'lifetime'      => 7200,
        'path'          => null,
        'domain'        => null,
        'secure'        => false,
        'httponly'      => true,
        'cache_limiter' => 'nocache',
    ];

    /**
     * Keys of $options are session settings, other keys are passed to ini_set() as `session.<key>`.
     *
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->flash = new SessionFlash($this->storage);

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                ini_set('session.' . $key, $value);
            }
        }
    }

    public function start(): void
    {
        if ($this->started) {
            throw new AlreadyStarted('Failed to start the session: Already started.');
        }

        if (headers_sent($file, $line) && filter_var(ini_get('session.use_cookies'), FILTER_VALIDATE_BOOLEAN)) {
            throw new HeadersSent(
                sprintf('Failed to start the session because headers have already been sent by "%s" at line %d.', $file, $line)
            );
        }

        $current = session_get_cookie_params();

        session_set_cookie_params(
            (int) ($this->options['lifetime'] ?: $current['lifetime']),
            $this->options['path'] ?: $current['path'],
            $this->options['domain'] ?: $current['domain'],
            (bool) $this->options['secure'],
            (bool) $this->options['httponly'],
        );
        session_name($this->options['name']);
        session_cache_limiter($this->options['cache_limiter']);

        if ($this->options['id']) {
            session_id($this->options['id']);
        }

        if (! session_start()) {
            throw new RuntimeException('Failed to start the session.');
        }

        $this->storage = &$_SESSION;
        $this->flash   = new SessionFlash($_SESSION);
    }

    public function regenerateId(): void
    {
        if (! $this->started) {
            throw new NotStarted('Cannot regenerate the session ID for non-active sessions.');
        }

        if (headers_sent()) {
            throw new HeadersSent('Headers have already been sent.');
        }

        if (! session_regenerate_id(true)) {
            throw new RuntimeException('The session ID could not be regenerated.');
        }
    }

    public function delete(): void
    {
        if (! $this->started) {
            return;
        }

        $this->flush();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                $this->name,
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        if (session_unset() === false) {
            throw new RuntimeException('The session could not be unset.');
        }

        if (session_destroy() === false) {
            throw new RuntimeException('The session could not be destroyed.');
        }
    }

    public function save(): void
    {
        session_write_close();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->storage;
    }

    public function set(string $key, mixed $value): void
    {
        $this->storage[$key] = $value;
    }

    public function setValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->storage[$key] = $value;
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->storage);
    }

    public function forget(string $key): void
    {
        unset($this->storage[$key]);
    }

    public function flush(): void
    {
        // assignment keeps the reference to $_SESSION
        $this->storage = [];
    }
}
