<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits;

class Memory implements Provider
{
    use Traits;

    /**
     * Cap on entries per group — without it, a long-running process (Scheduler::work(), a bulk
     * export) that touches many distinct keys grows this cache without bound. Evicts the oldest
     * entry (FIFO, not true LRU) once a group hits this size.
     *
     * @var int
     */
    private const int MAX_ENTRIES_PER_GROUP = 5000;

    /**
     * Adds data to the cache. $expiry accepts an absolute DateTime or a relative time string
     * (e.g. "+1 day", "+30 minutes") — anything the DateTime constructor understands.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param DateTime|string|null $expiry
     * @return mixed
     */
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): mixed
    {
        if (isset(self::$locks[ $group ][ $key ])) {
            return false;
        }

        if (is_string($expiry)) {
            $expiry = new DateTime($expiry);
        }

        $entry = self::$cache[ $group ][ $key ] ?? null;

        if ($entry !== null) {
            if ($entry['expiry'] === null || $entry['expiry'] > time()) {
                return $entry['value'];
            }
            unset(self::$cache[ $group ][ $key ]);
        }

        if (count(self::$cache[ $group ] ?? []) >= self::MAX_ENTRIES_PER_GROUP) {
            // Dropping one known key is O(1); array_shift() would rebuild the whole array.
            unset(self::$cache[ $group ][ array_key_first(self::$cache[ $group ]) ]);
        }

        self::$cache[ $group ][ $key ] = [
            'value'  => $value,
            'expiry' => $expiry?->getTimestamp(),
        ];

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group. The value never expires — use add()
     * with an $expiry for a TTL-bound entry.
     *
     * @param string     $key   The cache item key.
     * @param mixed|null $value The value to store in the cache.
     * @param string     $group The cache group or namespace. Defaults to 'default'.
     *
     * @return mixed Returns the value that was set.
     */
    public function set(string $key, mixed $value, string $group = 'default'): mixed
    {
        if (! isset(self::$cache[ $group ][ $key ]) && count(self::$cache[ $group ] ?? []) >= self::MAX_ENTRIES_PER_GROUP) {
            unset(self::$cache[ $group ][ array_key_first(self::$cache[ $group ]) ]);
        }

        // set() always means "no expiry", even if the key previously had one via add().
        self::$cache[ $group ][ $key ]['expiry'] = null;

        return self::$cache[ $group ][ $key ]['value'] = $value;
    }

    /**
     * Retrieves data from the cache. An entry whose expiry has passed is treated as a miss and
     * evicted.
     *
     * @param string $key
     * @param string $group
     * @param callable|null $callback
     * @return mixed
     */
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        // Fetch once instead of isset() + a second lookup for the value.
        $entry = self::$cache[ $group ][ $key ] ?? null;

        if ($entry !== null) {
            if ($entry['expiry'] === null || $entry['expiry'] > time()) {
                return $entry['value'];
            }
            unset(self::$cache[ $group ][ $key ]);
        }

        // $callback is already callable|null-typed, so a plain null check suffices here.
        if ($callback !== null) {
            return $this->add($key, $callback(), $group);
        }

        return null;
    }

    /**
     * Retrieves and removes data from the cache.
     *
     * @param string $key
     * @param string $group
     * @return mixed
     */
    public function pull(string $key, string $group = 'default'): mixed
    {
        $value = $this->get($key, $group);

        $this->forget($key, $group);

        return $value;
    }

    /**
     * Suspend the addition of data to the cache.
     *
     * @param callable $callback
     * @param string $key
     * @param string $group
     */
    public function suspend(callable $callback, string $key, string $group = 'default'): void
    {
        self::$locks[ $group ][ $key ] = true;

        $callback();

        unset(self::$locks[ $group ][ $key ]);
    }

    /**
     * Clears data from the cache.
     *
     * @param string $key
     * @param string $group
     * @return bool
     */
    public function forget(string $key = '', string $group = 'default'): bool
    {
        // Strict comparison: a key literally "0" is falsy and must not wipe the whole group.
        if ($key !== '') {
            unset(self::$cache[ $group ][ $key ]);
        } else {
            self::$cache[ $group ] = [];
        }
        return true;
    }

    /**
     * Increases the value of a key by a given amount. Expiry, if any, is preserved.
     *
     * @param string $key
     * @param int|float $amount
     * @param string $group
     * @return bool
     */
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        // Strict comparison — a key literally named "0" is falsy and must not be rejected here.
        if ($key === '') {
            return false;
        }

        // Read via the nested path, not a local var, to avoid a COW copy on the '+=' write below.
        $expiry = self::$cache[ $group ][ $key ]['expiry'] ?? null;

        if ($expiry !== null && $expiry <= time()) {
            return false;
        }

        if (! is_numeric(self::$cache[ $group ][ $key ]['value'] ?? null)) {
            return false;
        }

        self::$cache[ $group ][ $key ]['value'] += $amount;

        return true;
    }

    /**
     * Decreases the value of a key by a given amount. Expiry, if any, is preserved.
     *
     * @param string $key
     * @param int|float $amount
     * @param string $group
     * @return bool
     */
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }
}
