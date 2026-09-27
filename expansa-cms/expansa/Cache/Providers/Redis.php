<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits\Locks;
use Expansa\Cache\Traits\Memoizes;
use Expansa\Cache\Traits\Serializes;
use Redis as RedisClient;

/**
 * Redis (ext-redis) with native TTL. Values are memoized for the request.
 *
 * @package Expansa\Cache\Providers
 */
final class Redis implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    public function __construct(

        /**
         * Connected client, the connection is not opened here.
         */
        private readonly RedisClient $client,
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
        $ttl    = $expiry === null ? null : $expiry - time();
        if ($ttl !== null && $ttl <= 0) {
            return false;
        }

        $this->client->set($physicalKey, $this->serializeValue($value), $ttl);
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
            $this->client->del($this->physicalKey($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        // KEYS scans everything: switch to SCAN if a group can reach millions of keys
        $keys = $this->client->keys("$group:*");
        if ($keys !== []) {
            $this->client->del($keys);
        }

        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * Keeps the remaining TTL of the value.
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
        $ttl    = $this->client->ttl($physicalKey);

        $this->client->set($physicalKey, $this->serializeValue($value), $ttl > 0 ? $ttl : null);
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
        return "$group:$key";
    }
}
