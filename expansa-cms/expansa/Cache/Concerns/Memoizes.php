<?php

declare(strict_types=1);

namespace Expansa\Cache\Concerns;

use WeakMap;

/**
 * A request-local L1 cache in front of a slower L2 backend (disk, a DB table, a network
 * round-trip to Redis/Memcached). Reading the same key twice in one request — the common pattern
 * for Cache::get() called from several places against the same owner/group per page load —
 * costs one L2 round-trip instead of one per call.
 *
 * Scoped per *instance*, not per class, via a WeakMap keyed on $this: two providers of the same
 * class pointed at different physical resources (e.g. two File caches with different
 * directories) never see each other's memo, and an instance's memo is dropped automatically
 * once it's garbage collected — no manual cleanup, no risk of a recycled object id aliasing
 * stale data the way a plain spl_object_id()-keyed array would.
 *
 * The WeakMap holds one {@see MemoStore} object per instance rather than a plain array directly —
 * see that class for why (short version: avoiding an O(N²) blowup as a group grows).
 *
 * Values, including a legitimately cached `null`, are distinguished from "not memoized yet" via
 * array_key_exists(), never via isset()/null-checks.
 */
trait Memoizes
{
    /**
     * @var WeakMap<object, MemoStore>
     */
    private static WeakMap $memo;

    /**
     * This instance's memo store, created on first use.
     */
    private function memoStore(): MemoStore
    {
        self::$memo ??= new WeakMap();

        return self::$memo[ $this ] ??= new MemoStore();
    }

    /**
     * Returns the memoized value for $key in $group, or null if nothing is memoized. Callers
     * must check hasMemoized() first to tell an actual null value apart from "no entry".
     */
    private function memoized(string $group, string $key): mixed
    {
        return $this->memoStore()->data[ $group ][ $key ]['value'] ?? null;
    }

    /**
     * Whether $key in $group has been memoized in this instance's L1 cache, including a
     * legitimately cached null value.
     */
    private function hasMemoized(string $group, string $key): bool
    {
        return array_key_exists($key, $this->memoStore()->data[ $group ] ?? []);
    }

    /**
     * Stores $value in this instance's L1 cache for $key in $group and returns it unchanged, so
     * a call site can memoize and return in one expression.
     */
    private function memoize(string $group, string $key, mixed $value): mixed
    {
        $this->memoStore()->data[ $group ][ $key ] = ['value' => $value];

        return $value;
    }

    /**
     * Drops the memoized entry for $key in $group, so the next read goes back to the L2 backend.
     */
    private function forgetMemoized(string $group, string $key): void
    {
        unset($this->memoStore()->data[ $group ][ $key ]);
    }

    /**
     * Drops every memoized entry for $group, so the next read of any key in it goes back to the
     * L2 backend. Used after a whole-group forget() invalidates every physical key at once.
     */
    private function forgetMemoizedGroup(string $group): void
    {
        unset($this->memoStore()->data[ $group ]);
    }
}
