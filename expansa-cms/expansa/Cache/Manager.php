<?php

declare(strict_types=1);

namespace Expansa\Cache;

use Closure;
use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Providers\Apcu;
use Expansa\Cache\Providers\Database;
use Expansa\Cache\Providers\File;
use Expansa\Cache\Providers\Memcached;
use Expansa\Cache\Providers\Memory;
use Expansa\Cache\Providers\Redis;
use InvalidArgumentException;
use Memcached as MemcachedClient;
use Redis as RedisClient;

/**
 * Stores described by configuration and created on first use, the Cache facade instance.
 * Provider methods work with the default store; without configuration it is the request memory.
 *
 * @package Expansa\Cache
 */
final class Manager implements Provider
{
    /**
     * Store configs by name, each with a `driver` key.
     *
     * @var array<string, array{driver: string}&array<string, mixed>>
     */
    private array $config = [
        'memory' => ['driver' => 'memory'],
    ];

    /**
     * Store of the provider methods.
     */
    public private(set) string $defaultStore = 'memory';

    /**
     * Created stores by name.
     *
     * @var array<string, Provider>
     */
    public private(set) array $stores = [];

    /**
     * Custom drivers: get the store config, return a Provider.
     *
     * @var array<string, Closure>
     */
    private array $drivers = [];

    /**
     * Set the stores, created stores are dropped.
     *
     * @param array<string, array<string, mixed>> $stores  Configs by name: `['files' => ['driver' => 'file', 'path' => '...']]`.
     * @param string                              $default Store of the provider methods, the first store by default.
     * @return void
     * @throws InvalidArgumentException If the default store is not configured.
     */
    public function configure(array $stores, string $default = ''): void
    {
        $default = $default !== '' ? $default : (string) array_key_first($stores);

        if (! isset($stores[$default])) {
            throw new InvalidArgumentException("Default cache store [$default] is not configured.");
        }

        $this->config       = $stores;
        $this->defaultStore = $default;
        $this->stores       = [];
    }

    /**
     * Add a custom driver.
     *
     * @param string  $driver
     * @param Closure $factory Gets the store config, returns a Provider.
     * @return static
     */
    public function extend(string $driver, Closure $factory): static
    {
        $this->drivers[$driver] = $factory;

        return $this;
    }

    /**
     * Get a store, the default one without a name.
     *
     * @param string|null $name
     * @return Provider
     * @throws InvalidArgumentException For an unknown store or driver, or a missing option.
     */
    public function store(?string $name = null): Provider
    {
        $name ??= $this->defaultStore;

        return $this->stores[$name] ??= $this->resolve($name);
    }

    /**
     * Drop a created store, the next call creates it again.
     *
     * @param string|null $name The default store by default.
     * @return static
     */
    public function forgetStore(?string $name = null): static
    {
        unset($this->stores[$name ?? $this->defaultStore]);

        return $this;
    }

    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): bool
    {
        return $this->store()->add($key, $value, $group, $expiry);
    }

    public function set(string $key, mixed $value, string $group = 'default'): bool
    {
        return $this->store()->set($key, $value, $group);
    }

    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        return $this->store()->get($key, $group, $callback);
    }

    public function pull(string $key, string $group = 'default'): mixed
    {
        return $this->store()->pull($key, $group);
    }

    public function suspend(callable $callback, string $key, string $group = 'default'): void
    {
        $this->store()->suspend($callback, $key, $group);
    }

    public function forget(string $key = '', string $group = 'default'): bool
    {
        return $this->store()->forget($key, $group);
    }

    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->store()->increase($key, $amount, $group);
    }

    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->store()->decrease($key, $amount, $group);
    }

    /**
     * Create a store from its config.
     *
     * @param string $name
     * @return Provider
     * @throws InvalidArgumentException
     */
    private function resolve(string $name): Provider
    {
        $config = $this->config[$name] ?? null;
        if (! is_array($config) || ! isset($config['driver'])) {
            throw new InvalidArgumentException("Cache store [$name] is not configured.");
        }

        $driver = $config['driver'];
        $method = 'create' . ucfirst($driver) . 'Driver';

        return match (true) {
            isset($this->drivers[$driver]) => ($this->drivers[$driver])($config),
            method_exists($this, $method)  => $this->{$method}($config, $name),
            default                        => throw new InvalidArgumentException("Cache driver [$driver] of [$name] is unknown."),
        };
    }

    private function createMemoryDriver(): Memory
    {
        return new Memory();
    }

    private function createFileDriver(array $config, string $name): File
    {
        return new File($this->required($config, 'path', $name));
    }

    private function createApcuDriver(): Apcu
    {
        return new Apcu();
    }

    /**
     * Connect to the Redis server of the config.
     *
     * @param array{host?: string, port?: int, password?: string, database?: int} $config
     */
    private function createRedisDriver(array $config): Redis
    {
        $client = new RedisClient();
        $client->connect($config['host'] ?? '127.0.0.1', (int) ($config['port'] ?? 6379));

        if (isset($config['password'])) {
            $client->auth($config['password']);
        }

        if (isset($config['database'])) {
            $client->select((int) $config['database']);
        }

        return new Redis($client);
    }

    /**
     * Add the Memcached servers of the config.
     *
     * @param array{servers?: list<array{0: string, 1?: int, 2?: int}>} $config Host, port, weight.
     */
    private function createMemcachedDriver(array $config): Memcached
    {
        $client = new MemcachedClient();
        $client->addServers($config['servers'] ?? [['127.0.0.1', 11211]]);

        return new Memcached($client);
    }

    /**
     * The connection comes from outside, so Cache does not depend on the Database facade.
     *
     * @param array{connection: Closure} $config Returns an Expansa\Database\Query\Builder.
     */
    private function createDatabaseDriver(array $config, string $name): Database
    {
        return new Database(($this->required($config, 'connection', $name))());
    }

    private function required(array $config, string $key, string $name): mixed
    {
        return $config[$key] ?? throw new InvalidArgumentException("Cache store [$name] requires the \"$key\" option.");
    }
}
