<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits\Locks;
use Expansa\Cache\Traits\Memoizes;
use Expansa\Cache\Traits\Serializes;
use Memcached as MemcachedClient;

/**
 * Memcached (ext-memcached) with native TTL.
 *
 * Memcached can't list keys, so forgetting a group bumps its version, which is part of every
 * physical key: old entries are orphaned. Values and group versions are memoized for the request.
 *
 * @package Expansa\Cache\Providers
 */
final class Memcached implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    /**
     * Group versions read in this request; per instance, clients may point to different servers.
     *
     * @var array<string, int>
     */
    private array $groupVersions = [];

    public function __construct(

        /**
         * Client with the servers added, the connection is not configured here.
         */
        private readonly MemcachedClient $client,
    ) {}

    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): bool
    {
        if ($this->isLocked($group, $key) || $this->hasMemoized($group, $key)) {
            return false;
        }

        $physicalKey = $this->physicalKey($key, $group);
        $existing    = $this->client->get($physicalKey);
        if ($existing !== false) {
            $this->memoize($group, $key, $this->unserializeValue($existing));

            return false;
        }

        $expiry = is_string($expiry) ? new DateTime($expiry)->getTimestamp() : $expiry?->getTimestamp();
        if ($expiry !== null && $expiry <= time()) {
            return false;
        }

        // 0 never expires
        $this->client->set($physicalKey, $this->serializeValue($value), $expiry === null ? 0 : max(1, $expiry - time()));
        $this->memoize($group, $key, $value);

        return true;
    }

    #[\Override]
    public function set(string $key, mixed $value, string $group = 'default'): bool
    {
        $this->client->set($this->physicalKey($key, $group), $this->serializeValue($value));
        $this->memoize($group, $key, $value);

        return true;
    }

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

        if ($callback === null) {
            return null;
        }

        $value = $callback();
        $this->add($key, $value, $group);

        return $value;
    }

    #[\Override]
    public function pull(string $key, string $group = 'default'): mixed
    {
        $value = $this->get($key, $group);

        $this->forget($key, $group);

        return $value;
    }

    #[\Override]
    public function forget(string $key = '', string $group = 'default'): bool
    {
        if ($key !== '') {
            $this->client->delete($this->physicalKey($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        // increment() creates a missing key only over the binary protocol, add() makes it work over both
        $versionKey = $this->versionKey($group);
        $this->client->add($versionKey, 1);
        $version = $this->client->increment($versionKey);

        $this->groupVersions[$group] = $version !== false ? $version : 2;
        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * Memcached can't read the remaining TTL, so the expiry of the value is lost.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
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

    #[\Override]
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
        if (! isset($this->groupVersions[$group])) {
            $version = $this->client->get($this->versionKey($group));

            $this->groupVersions[$group] = $version === false ? 1 : (int) $version;
        }

        return $this->groupVersions[$group];
    }

    private function versionKey(string $group): string
    {
        return "__version__:$group";
    }
}
