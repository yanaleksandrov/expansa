<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits;
use Memcached as MemcachedClient;

/**
 * A cache provider backed by Memcached (ext-memcached). Takes an already-connected client — this
 * class does not open the connection itself. TTL is native, like Redis.
 *
 * Memcached has no key-enumeration command, so a whole-group forget() can't delete matching keys
 * directly. Instead each group has a version counter baked into its physical keys; clearing a
 * group bumps the counter, orphaning old entries rather than deleting them outright.
 */
class Memcached implements Provider
{
    use Traits;

    public function __construct(private readonly MemcachedClient $client)
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

        // An expiry already in the past is never actually stored, matching Memory's behavior of
        // an add()'d-then-immediately-expired entry never being visible to a later get().
        if ($expiry === null || $expiry->getTimestamp() > time()) {
            $this->client->set($physicalKey, serialize($value), $this->ttlSeconds($expiry));
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
            $this->client->delete($this->physicalKey($key, $group));

            return true;
        }

        // No key enumeration in Memcached — bump the group's version instead of deleting.
        $this->client->increment($this->versionKey($group), 1, 2);

        return true;
    }

    /**
     * Increases the value of a key by a given amount. Memcached exposes no way to read a key's
     * remaining TTL, so unlike Memory/Database/Redis, any existing expiry is not preserved here.
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

        $this->client->set($physicalKey, serialize($value + $amount));

        return true;
    }

    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }

    private function physicalKey(string $key, string $group): string
    {
        return "$group:v{$this->groupVersion($group)}:$key";
    }

    private function groupVersion(string $group): int
    {
        $version = $this->client->get($this->versionKey($group));

        return $version === false ? 1 : (int) $version;
    }

    private function versionKey(string $group): string
    {
        return "__version__:$group";
    }

    private function ttlSeconds(?DateTime $expiry): int
    {
        // Memcached's own convention: 0 means "never expire".
        return $expiry === null ? 0 : max(1, $expiry->getTimestamp() - time());
    }
}
