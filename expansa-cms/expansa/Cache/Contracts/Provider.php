<?php

declare(strict_types=1);

namespace Expansa\Cache\Contracts;

use DateTime;

/**
 * Cache storage: values by key within a group, a group can be forgotten at once.
 *
 * @package Expansa\Cache\Contracts
 */
interface Provider
{
    /**
     * Store a value unless the key is already present and not expired.
     *
     * @param string               $key
     * @param mixed                $value
     * @param string               $group
     * @param DateTime|string|null $expiry Absolute time or a relative string like "+1 day", null never expires.
     * @return bool False if the key exists, is suspended or the expiry has passed.
     */
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): bool;

    /**
     * Store a value without expiry, overwriting an existing one.
     *
     * @param string $key
     * @param mixed  $value
     * @param string $group
     * @return bool
     */
    public function set(string $key, mixed $value, string $group = 'default'): bool;

    /**
     * Get a value; on a miss the callback result is added and returned.
     *
     * @param string        $key
     * @param string        $group
     * @param callable|null $callback
     * @return mixed Null on a miss without a callback.
     */
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed;

    /**
     * Get a value and forget it.
     *
     * @param string $key
     * @param string $group
     * @return mixed
     */
    public function pull(string $key, string $group = 'default'): mixed;

    /**
     * Reject add() of the key while the callback runs.
     *
     * @param callable $callback
     * @param string   $key
     * @param string   $group
     * @return void
     */
    public function suspend(callable $callback, string $key, string $group = 'default'): void;

    /**
     * Forget a key, or the whole group with an empty key.
     *
     * @param string $key
     * @param string $group
     * @return bool
     */
    public function forget(string $key = '', string $group = 'default'): bool;

    /**
     * Increase a numeric value.
     *
     * @param string    $key
     * @param int|float $amount
     * @param string    $group
     * @return bool False if the key is missing or not numeric.
     */
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool;

    /**
     * Decrease a numeric value.
     *
     * @param string    $key
     * @param int|float $amount
     * @param string    $group
     * @return bool False if the key is missing or not numeric.
     */
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool;
}
