<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Concerns\Locks;
use Expansa\Cache\Concerns\Memoizes;
use Expansa\Cache\Concerns\Serializes;
use Expansa\Cache\Contracts\Provider;
use Memcached as MemcachedClient;
use WeakMap;

/**
 * A cache provider backed by Memcached (ext-memcached). Takes an already-connected client — this
 * class does not open the connection itself. TTL is native, like Redis.
 *
 * A request-local L1 memo (see Memoizes) sits in front of the network round-trip to the
 * Memcached server, and the group's version number (see groupVersion()) is memoized the same
 * way — see Apcu's docblock for the consistency trade-off this implies.
 *
 * Memcached has no key-enumeration command, so a whole-group forget() can't delete matching keys
 * directly. Instead each group has a version counter baked into its physical keys; clearing a
 * group bumps the counter, orphaning old entries rather than deleting them outright.
 */
class Memcached implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    /**
     * Per-instance group version memo. Keyed by instance (not just by class, like Apcu's plain
     * static array) because, unlike APCu, a Memcached client is constructor-supplied and two
     * instances of this class can point at different servers — sharing one class-wide version
     * cache between them would be wrong.
     *
     * @var WeakMap<self, array<string, int>>
     */
    private static WeakMap $groupVersions;

    public function __construct(

        /**
         * Client with servers already added; this class does not configure the connection.
         */
        private readonly MemcachedClient $client,
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

        // An expiry already in the past is never actually stored, matching Memory's behavior of
        // an add()'d-then-immediately-expired entry never being visible to a later get().
        if ($expiry === null || $expiry->getTimestamp() > time()) {
            $this->client->set($physicalKey, $this->serializeValue($value), $this->ttlSeconds($expiry));
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
            $this->client->delete($this->physicalKey($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        // No key enumeration in Memcached — bump the group's version instead of deleting.
        $versionKey = $this->versionKey($group);

        // increment()'s $initial_value only auto-creates a missing key over the binary protocol
        // (silently failing and returning false otherwise, leaving the key never created and
        // every future forget() a no-op) — add() guarantees the key exists first, no-op if it
        // already does, so the plain increment() below works under either protocol.
        $this->client->add($versionKey, 1);
        $bumped = $this->client->increment($versionKey);

        $this->rememberGroupVersion($group, $bumped !== false ? $bumped : 2);
        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * Increases the value of a key by a given amount. Memcached exposes no way to read a key's
     * remaining TTL, so unlike Memory/Database/Redis, any existing expiry is not preserved here.
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

        $this->client->set($physicalKey, $this->serializeValue($value));
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
     * The physical Memcached key for $key/$group, carrying the group's current version so a
     * bumped group transparently orphans every key written under the old version.
     */
    private function physicalKey(string $key, string $group): string
    {
        return "$group:v{$this->groupVersion($group)}:$key";
    }

    /**
     * The group's current version number, memoized per instance per request so only the first
     * call for a given group on this client in this process pays for a network round-trip.
     */
    private function groupVersion(string $group): int
    {
        self::$groupVersions ??= new WeakMap();

        $versions = self::$groupVersions[ $this ] ?? [];

        if (isset($versions[ $group ])) {
            return $versions[ $group ];
        }

        $version = $this->client->get($this->versionKey($group));
        $version = $version === false ? 1 : (int) $version;

        $this->rememberGroupVersion($group, $version);

        return $version;
    }

    /**
     * Updates this instance's memoized version number for $group, initializing the WeakMap on
     * first use.
     */
    private function rememberGroupVersion(string $group, int $version): void
    {
        self::$groupVersions ??= new WeakMap();

        $versions            = self::$groupVersions[ $this ] ?? [];
        $versions[ $group ]  = $version;

        self::$groupVersions[ $this ] = $versions;
    }

    /**
     * The Memcached key holding $group's version counter.
     */
    private function versionKey(string $group): string
    {
        return "__version__:$group";
    }

    /**
     * Converts an expiry into the TTL (in seconds) the client's set() expects.
     */
    private function ttlSeconds(?DateTime $expiry): int
    {
        // Memcached's own convention: 0 means "never expire".
        return $expiry === null ? 0 : max(1, $expiry->getTimestamp() - time());
    }
}
