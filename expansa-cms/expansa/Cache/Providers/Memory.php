<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Traits;
use Expansa\Facades\Db;

class Memory
{
    use Traits;

    /**
     * Cap on entries per group — without it, a long-running process (Scheduler::work(), a bulk
     * export) that touches many distinct keys grows this cache without bound. Evicts the oldest
     * entry (FIFO, not true LRU) once a group hits this size.
     *
     * @var int
     */
    private const MAX_ENTRIES_PER_GROUP = 5000;

    /**
     * Adds data to the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param DateTime|null $expiry TODO: add string support, like "+1 day"
     * @param string $group
     * @return mixed
     */
    public function add(string $key, mixed $value, string $group = 'default', ?DateTime $expiry = null): mixed
    {
        if (isset(self::$locks[ $group ][ $key ])) {
            return false;
        }

        if (isset(self::$cache[ $group ][ $key ])) {
            return self::$cache[ $group ][ $key ]['value'];
        }

        if (count(self::$cache[ $group ] ?? []) >= self::MAX_ENTRIES_PER_GROUP) {
            // unset(), not array_shift() — the latter always rebuilds the whole array (O(n),
            // even for string keys), while dropping one known key is a plain O(1) hash removal.
            unset(self::$cache[ $group ][ array_key_first(self::$cache[ $group ]) ]);
        }

        self::$cache[ $group ][ $key ] = [
            'value'  => $value,
            'expiry' => $expiry?->getTimestamp(),
        ];

        if ($expiry instanceof DateTime) {
            Db::insert(
                self::$table,
                [
                    'key'        => $key,
                    'value'      => $value,
                    'expiration' => $expiry->getTimestamp(),
                ]
            );
        }

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group.
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

        return self::$cache[$group][$key]['value'] = $value;
    }

    /**
     * Retrieves data from the cache.
     *
     * @param string $key
     * @param callable|null $callback
     * @param string $group
     * @return mixed
     */
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        if (isset(self::$cache[ $group ][ $key ])) {
            return self::$cache[ $group ][ $key ]['value'];
        }

        if (is_callable($callback)) {
            return $this->add($key, call_user_func($callback), $group);
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

        $this->forget($key);

        return $value;
    }

    /**
     * Suspend the addition of data to the cache.
     *
     * @param string $key
     * @param callable $callback
     * @param string $group
     */
    public function suspend(callable $callback, string $key, string $group = 'default'): void
    {
        self::$locks[ $group ][ $key ] = true;

        call_user_func($callback);

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
        if ($key) {
            unset(self::$cache[ $group ][ $key ]);
        } else {
            self::$cache[ $group ] = [];
        }
        return true;
    }

    /**
     * Increases the value of a key by a given amount.
     *
     * @param string $key
     * @param int|float $amount
     * @param string $group
     * @return mixed
     */
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key && is_numeric(self::$cache[ $group ][ $key ]['value'] ?? null)) {
            self::$cache[ $group ][ $key ]['value'] += $amount;

            return true;
        }
        return false;
    }

    /**
     * Decreases the value of a key by a given amount.
     *
     * @param string $key
     * @param int|float $amount
     * @param string $group
     * @return mixed
     */
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key && is_numeric(self::$cache[ $group ][ $key ]['value'] ?? null)) {
            self::$cache[ $group ][ $key ]['value'] -= $amount;

            return true;
        }
        return false;
    }
}
