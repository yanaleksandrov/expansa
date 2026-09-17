<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits;
use Redis as RedisClient;

/**
 * A cache provider backed by Redis (ext-redis). Takes an already-connected client — this class
 * does not open the connection itself. TTL is native: Redis expires keys on its own, so reads
 * never need a manual "is this stale" check.
 */
class Redis implements Provider
{
    use Traits;

    public function __construct(private readonly RedisClient $client)
    {
    }

    /**
     * $expiry accepts an absolute DateTime or a relative time string (e.g. "+1 day").
     */
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): mixed
    {
        if (isset(self::$locks[ $group ][ $key ])) {
            return false;
        }

        if (is_string($expiry)) {
            $expiry = new DateTime($expiry);
        }

        $physicalKey = $this->physicalKey($key, $group);
        $existing    = $this->client->get($physicalKey);

        if ($existing !== false) {
            return unserialize($existing);
        }

        $ttl = $this->ttlSeconds($expiry);

        // An expiry already in the past is never actually stored, matching Memory's behavior of
        // an add()'d-then-immediately-expired entry never being visible to a later get().
        if ($ttl === null || $ttl > 0) {
            $this->client->set($physicalKey, serialize($value), $ttl);
        }

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group. The value never expires — use add()
     * with an $expiry for a TTL-bound entry.
     */
    public function set(string $key, mixed $value, string $group = 'default'): mixed
    {
        $this->client->set($this->physicalKey($key, $group), serialize($value));

        return $value;
    }

    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        $value = $this->client->get($this->physicalKey($key, $group));

        if ($value !== false) {
            return unserialize($value);
        }

        if ($callback !== null) {
            return $this->add($key, $callback(), $group);
        }

        return null;
    }

    public function pull(string $key, string $group = 'default'): mixed
    {
        $value = $this->get($key, $group);

        $this->forget($key, $group);

        return $value;
    }

    public function suspend(callable $callback, string $key, string $group = 'default'): void
    {
        self::$locks[ $group ][ $key ] = true;

        $callback();

        unset(self::$locks[ $group ][ $key ]);
    }

    public function forget(string $key = '', string $group = 'default'): bool
    {
        if ($key !== '') {
            $this->client->del($this->physicalKey($key, $group));

            return true;
        }

        // KEYS is a full scan; fine at this cache's dev scale, but swap for a cursor-based SCAN
        // loop if a group can ever grow into the millions of keys.
        $keys = $this->client->keys("$group:*");

        if ($keys !== []) {
            $this->client->del($keys);
        }

        return true;
    }

    /**
     * Increases the value of a key by a given amount, preserving its remaining TTL if any.
     */
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key === '') {
            return false;
        }

        $physicalKey = $this->physicalKey($key, $group);
        $raw         = $this->client->get($physicalKey);

        if ($raw === false) {
            return false;
        }

        $value = unserialize($raw);

        if (! is_numeric($value)) {
            return false;
        }

        $ttl = $this->client->ttl($physicalKey);

        $this->client->set($physicalKey, serialize($value + $amount), $ttl > 0 ? $ttl : null);

        return true;
    }

    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }

    private function physicalKey(string $key, string $group): string
    {
        return "$group:$key";
    }

    private function ttlSeconds(?DateTime $expiry): ?int
    {
        return $expiry === null ? null : $expiry->getTimestamp() - time();
    }
}
