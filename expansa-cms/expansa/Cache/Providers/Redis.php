<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Concerns\Locks;
use Expansa\Cache\Concerns\Memoizes;
use Expansa\Cache\Concerns\Serializes;
use Expansa\Cache\Contracts\Provider;
use Redis as RedisClient;

/**
 * A cache provider backed by Redis (ext-redis). Takes an already-connected client — this class
 * does not open the connection itself. TTL is native: Redis expires keys on its own, so reads
 * never need a manual "is this stale" check.
 *
 * A request-local L1 memo (see Memoizes) sits in front of the network round-trip to Redis: a key
 * read twice in the same request costs one round-trip, not two. That memo can go stale if
 * another process writes the same key mid-request; an accepted trade-off bounded to this
 * request's lifetime (see Memoizes).
 */
class Redis implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    public function __construct(

        /**
         * Already connected Redis client; the connection is not opened here.
         */
        private readonly RedisClient $client,
    ) {} // phpcs:ignore

    /**
     * $expiry accepts an absolute DateTime or a relative time string (e.g. "+1 day").
     */
    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): mixed
    {
        if ($this->isLocked($group, $key)) {
            return false;
        }

        if ($this->hasMemoized($group, $key)) {
            return $this->memoized($group, $key);
        }

        if (is_string($expiry)) {
            $expiry = new DateTime($expiry);
        }

        $physicalKey = $this->physicalKey($key, $group);
        $existing    = $this->client->get($physicalKey);

        if ($existing !== false) {
            return $this->memoize($group, $key, $this->unserializeValue($existing));
        }

        $ttl = $this->ttlSeconds($expiry);

        // An expiry already in the past is never actually stored, matching Memory's behavior of
        // an add()'d-then-immediately-expired entry never being visible to a later get().
        if ($ttl === null || $ttl > 0) {
            $this->client->set($physicalKey, $this->serializeValue($value), $ttl);
            $this->memoize($group, $key, $value);
        }

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group. The value never expires — use add()
     * with an $expiry for a TTL-bound entry.
     */
    #[\Override]
    public function set(string $key, mixed $value, string $group = 'default'): mixed
    {
        $this->client->set($this->physicalKey($key, $group), $this->serializeValue($value));

        return $this->memoize($group, $key, $value);
    }

    /**
     * Retrieves data from the cache, optionally populating it via $callback on a miss.
     */
    #[\Override]
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        if ($this->hasMemoized($group, $key)) {
            return $this->memoized($group, $key);
        }

        $value = $this->client->get($this->physicalKey($key, $group));

        if ($value !== false) {
            return $this->memoize($group, $key, $this->unserializeValue($value));
        }

        if ($callback !== null) {
            return $this->add($key, $callback(), $group);
        }

        return null;
    }

    /**
     * Retrieves and removes data from the cache.
     */
    #[\Override]
    public function pull(string $key, string $group = 'default'): mixed
    {
        $value = $this->get($key, $group);

        $this->forget($key, $group);

        return $value;
    }

    /**
     * Clears data from the cache. An empty $key clears the whole group.
     */
    #[\Override]
    public function forget(string $key = '', string $group = 'default'): bool
    {
        if ($key !== '') {
            $this->client->del($this->physicalKey($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        // KEYS is a full scan; fine at this cache's dev scale, but swap for a cursor-based SCAN
        // loop if a group can ever grow into the millions of keys.
        $keys = $this->client->keys("$group:*");

        if ($keys !== []) {
            $this->client->del($keys);
        }

        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * Increases the value of a key by a given amount, preserving its remaining TTL if any.
     */
    #[\Override]
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

        $value = $this->unserializeValue($raw);

        if (! is_numeric($value)) {
            return false;
        }

        $value += $amount;

        $ttl = $this->client->ttl($physicalKey);

        $this->client->set($physicalKey, $this->serializeValue($value), $ttl > 0 ? $ttl : null);
        $this->memoize($group, $key, $value);

        return true;
    }

    /**
     * Decreases the value of a key by a given amount.
     */
    #[\Override]
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }

    /**
     * The physical Redis key for $key/$group.
     */
    private function physicalKey(string $key, string $group): string
    {
        return "$group:$key";
    }

    /**
     * Converts an expiry into the TTL (in seconds) the client's set() expects; null means no
     * expiry.
     */
    private function ttlSeconds(?DateTime $expiry): ?int
    {
        return $expiry === null ? null : $expiry->getTimestamp() - time();
    }
}
