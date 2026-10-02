<?php

declare(strict_types=1);

namespace Expansa\Session;

use Closure;
use Expansa\Session\Contracts\Flash;
use Expansa\Session\Contracts\Lifecycle;
use Expansa\Session\Contracts\Session;
use Expansa\Session\Providers\Memory;
use Expansa\Session\Providers\Native;
use InvalidArgumentException;

/**
 * The session of the request with the configured driver, created on first use; the Session facade instance.
 * Session and lifecycle methods go to the driver; without configuration it is the PHP native session.
 *
 * @package Expansa\Session
 */
final class Manager implements Session, Lifecycle
{
    /**
     * Driver name: `native`, `memory` or a custom one from extend().
     *
     * @var string
     */
    public private(set) string $driverName = 'native';

    /**
     * Options of the driver, see the Native and Memory providers.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * Custom drivers: get the options, return a Session & Lifecycle.
     *
     * @var array<string, Closure>
     */
    private array $drivers = [];

    /**
     * Driver created on first use.
     *
     * @var null|(Session&Lifecycle)
     */
    private (Session&Lifecycle)|null $driver = null;

    public Flash $flash {
        get => $this->driver()->flash;
    }

    public bool $started {
        get => $this->driver()->started;
    }

    public string $id {
        get => $this->driver()->id;
    }

    public string $name {
        get => $this->driver()->name;
    }

    /**
     * Set the driver and its options, the created driver is dropped.
     *
     * @param string               $driver  `native`, `memory` or a custom one.
     * @param array<string, mixed> $options `name`, `lifetime`, `path`, `domain`, `secure`, `httponly`,
     *                                      `cache_limiter` for native; other keys go to ini_set('session.<key>').
     * @return void
     */
    public function configure(string $driver = 'native', array $options = []): void
    {
        $this->driverName = $driver;
        $this->options    = $options;
        $this->driver     = null;
    }

    /**
     * Add a custom driver.
     *
     * @param string  $driver
     * @param Closure $factory Gets the options, returns a Session & Lifecycle.
     * @return static
     */
    public function extend(string $driver, Closure $factory): static
    {
        $this->drivers[$driver] = $factory;

        return $this;
    }

    /**
     * Get the driver of the request, created on the first call.
     *
     * @return Session&Lifecycle
     * @throws InvalidArgumentException For an unknown driver.
     */
    public function driver(): Session&Lifecycle
    {
        return $this->driver ??= $this->resolve();
    }

    /**
     * Get the flash messages, for the facade: it has no access to properties.
     *
     * @return Flash
     */
    public function getFlash(): Flash
    {
        return $this->driver()->flash;
    }

    /**
     * Whether the session is started, for the facade.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->driver()->started;
    }

    public function start(): void
    {
        $this->driver()->start();
    }

    public function regenerateId(): void
    {
        $this->driver()->regenerateId();
    }

    public function delete(): void
    {
        $this->driver()->delete();
    }

    public function save(): void
    {
        $this->driver()->save();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver()->get($key, $default);
    }

    public function all(): array
    {
        return $this->driver()->all();
    }

    public function set(string $key, mixed $value): void
    {
        $this->driver()->set($key, $value);
    }

    public function setValues(array $values): void
    {
        $this->driver()->setValues($values);
    }

    public function has(string $key): bool
    {
        return $this->driver()->has($key);
    }

    public function forget(string $key): void
    {
        $this->driver()->forget($key);
    }

    public function flush(): void
    {
        $this->driver()->flush();
    }

    /**
     * Create the configured driver.
     *
     * @return Session&Lifecycle
     * @throws InvalidArgumentException
     */
    private function resolve(): Session&Lifecycle
    {
        $driver = $this->driverName;
        $method = 'create' . ucfirst($driver) . 'Driver';

        return match (true) {
            isset($this->drivers[$driver]) => ($this->drivers[$driver])($this->options),
            method_exists($this, $method)  => $this->{$method}($this->options),
            default                        => throw new InvalidArgumentException("Session driver [$driver] is unknown."),
        };
    }

    private function createNativeDriver(array $options): Native
    {
        return new Native($options);
    }

    private function createMemoryDriver(array $options): Memory
    {
        return new Memory($options['name'] ?? 'app');
    }
}
