<?php

declare(strict_types=1);

namespace Expansa\Cache\Traits;

use Expansa\Cache\Internal\MemoStore;
use WeakMap;

/**
 * Request-local memo in front of a slow backend (disk, table, network):
 * a key read twice in a request costs one backend call.
 *
 * Kept per instance in a WeakMap: two providers of one class with different backends
 * never share values, and the memo goes away with its provider. Another process may change
 * a memoized key, the value stays stale until the end of the request.
 *
 * @package Expansa\Cache\Traits
 */
trait Memoizes
{
    /**
     * Memo of each provider instance.
     *
     * @var WeakMap<object, MemoStore>
     */
    private static WeakMap $memo;

    private function memoStore(): MemoStore
    {
        self::$memo ??= new WeakMap();

        return self::$memo[$this] ??= new MemoStore();
    }

    /**
     * Get a memoized value, null also for a missing one: check hasMemoized() first.
     *
     * @param string $group
     * @param string $key
     * @return mixed
     */
    private function memoized(string $group, string $key): mixed
    {
        return $this->memoStore()->data[$group][$key]['value'] ?? null;
    }

    private function hasMemoized(string $group, string $key): bool
    {
        return array_key_exists($key, $this->memoStore()->data[$group] ?? []);
    }

    /**
     * Memoize a value and return it.
     *
     * @param string $group
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    private function memoize(string $group, string $key, mixed $value): mixed
    {
        $this->memoStore()->data[$group][$key] = ['value' => $value];

        return $value;
    }

    private function forgetMemoized(string $group, string $key): void
    {
        unset($this->memoStore()->data[$group][$key]);
    }

    private function forgetMemoizedGroup(string $group): void
    {
        unset($this->memoStore()->data[$group]);
    }
}
