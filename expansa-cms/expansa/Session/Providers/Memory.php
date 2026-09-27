<?php

declare(strict_types=1);

namespace Expansa\Session\Providers;

use Expansa\Session\Contracts\Flash;
use Expansa\Session\Contracts\Manager;
use Expansa\Session\Contracts\Session;
use Expansa\Session\Internal\Flash as SessionFlash;

/**
 * Session in an array that lives until the end of the request: for tests and the console.
 *
 * @package Expansa\Session
 */
final class Memory implements Session, Manager
{
    public readonly Flash $flash;

    public private(set) bool $started = false;

    public private(set) string $id = '';

    /**
     * @var array<string, mixed>
     */
    private array $storage = [];

    public function __construct(

        /**
         * Session name.
         */
        public readonly string $name = 'app',
    ) {
        $this->flash = new SessionFlash($this->storage);
    }

    public function start(): void
    {
        if ($this->id === '') {
            $this->regenerateId();
        }

        $this->started = true;
    }

    public function regenerateId(): void
    {
        $this->id = str_replace('.', '', uniqid('sess_', true));
    }

    public function delete(): void
    {
        $this->flush();
        $this->regenerateId();
    }

    public function save(): void
    {}

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
        $this->storage = [];
    }
}
