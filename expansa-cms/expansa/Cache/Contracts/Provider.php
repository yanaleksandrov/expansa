<?php

declare(strict_types=1);

namespace Expansa\Cache\Contracts;

use DateTime;

interface Provider
{
    /**
     * Adds data to the cache. Does nothing if the key is already present (and not expired).
     * $expiry accepts an absolute DateTime or a relative time string (e.g. "+1 day").
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param DateTime|string|null $expiry
     * @return mixed
     */
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): mixed;

    /**
     * Sets a value in the cache for a given key and group, overwriting any existing entry.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return mixed
     */
    public function set(string $key, mixed $value, string $group = 'default'): mixed;

    /**
     * Retrieves data from the cache, optionally populating it via $callback on a miss.
     *
     * @param string $key
     * @param string $group
     * @param callable|null $callback
     * @return mixed
     */
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed;

    /**
     * Retrieves and removes data from the cache.
     *
     * @param string $key
     * @param string $group
     * @return mixed
     */
    public function pull(string $key, string $group = 'default'): mixed;

    /**
     * Suspends the addition of data to the cache for the duration of $callback.
     *
     * @param callable $callback
     * @param string $key
     * @param string $group
     */
    public function suspend(callable $callback, string $key, string $group = 'default'): void;

    /**
     * Clears data from the cache. An empty $key clears the whole group.
     *
     * @param string $key
     * @param string $group
     * @return bool
     */
    public function forget(string $key = '', string $group = 'default'): bool;

    /**
     * Increases the value of a numeric key by a given amount.
     *
     * @param string $key
     * @param int|float $amount
     * @param string $group
     * @return bool
     */
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool;

    /**
     * Decreases the value of a numeric key by a given amount.
     *
     * @param string $key
     * @param int|float $amount
     * @param string $group
     * @return bool
     */
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool;
}
