<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Concerns\Locks;
use Expansa\Cache\Concerns\Memoizes;
use Expansa\Cache\Contracts\Provider;

/**
 * A cache provider backed by APCu — shared memory local to this machine, visible to every
 * PHP-FPM worker process (unlike Memory, which only lives inside a single process/request).
 * Requires ext-apcu; no server, connection or serialization step needed.
 *
 * A request-local L1 memo (see Memoizes) sits in front of APCu itself: a key read twice in the
 * same request costs one apcu_fetch(), not two. The group's version number (see groupVersion())
 * is memoized the same way, so a whole request only pays for that lookup once per group instead
 * of on every single call. Both memos can go stale if another worker process writes the same
 * key/group mid-request; that's an accepted trade-off (see Memoizes) bounded to this request's
 * lifetime.
 *
 * APCu has no key-enumeration call cheap enough to rely on, so a whole-group forget() uses a
 * version-counter trick: clearing a group bumps a counter baked into the physical key, orphaning
 * old entries instead of deleting them (they expire/evict on their own).
 */
class Apcu implements Provider
{
    use Locks;
    use Memoizes;

    /**
     * @var array<string, int>
     */
    private static array $groupVersions = [];

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
        $existing    = apcu_fetch($physicalKey, $success);

        if ($success) {
            return $this->memoize($group, $key, $existing);
        }

        // An expiry already in the past is never actually stored, matching Memory's behavior of
        // an add()'d-then-immediately-expired entry never being visible to a later get().
        if ($expiry === null || $expiry->getTimestamp() > time()) {
            apcu_store($physicalKey, $value, $this->ttlSeconds($expiry));
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
        apcu_store($this->physicalKey($key, $group), $value);

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

        $value = apcu_fetch($this->physicalKey($key, $group), $success);

        if ($success) {
            return $this->memoize($group, $key, $value);
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
            apcu_delete($this->physicalKey($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        $versionKey = $this->versionKey($group);

        // apcu_inc() would auto-create a missing key at 0 then add the step, landing on the same
        // value groupVersion() already assumes by default — old keys wouldn't be orphaned. Only
        // increment an existing counter; otherwise jump straight to 2 to guarantee a bump.
        if (apcu_exists($versionKey)) {
            $bumped = apcu_inc($versionKey);
        } else {
            $bumped = 2;
            apcu_store($versionKey, $bumped);
        }

        self::$groupVersions[ $group ] = (int) $bumped;
        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * Increases the value of a key by a given amount. APCu exposes no way to read a key's
     * remaining TTL, so unlike Memory/Database/Redis, any existing expiry is not preserved here.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key === '') {
            return false;
        }

        $physicalKey = $this->physicalKey($key, $group);
        $value       = apcu_fetch($physicalKey, $success);

        if (! $success || ! is_numeric($value)) {
            return false;
        }

        $value += $amount;

        apcu_store($physicalKey, $value);
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
     * The physical APCu key for $key/$group, carrying the group's current version so a bumped
     * group transparently orphans every key written under the old version.
     */
    private function physicalKey(string $key, string $group): string
    {
        return "$group:v{$this->groupVersion($group)}:$key";
    }

    /**
     * The group's current version number, memoized per request so only the first call for a
     * given group in this process pays for an apcu_fetch().
     */
    private function groupVersion(string $group): int
    {
        return self::$groupVersions[ $group ] ??= (function () use ($group): int {
            $version = apcu_fetch($this->versionKey($group), $success);

            return $success ? (int) $version : 1;
        })();
    }

    /**
     * The APCu key holding $group's version counter.
     */
    private function versionKey(string $group): string
    {
        return "__version__:$group";
    }

    /**
     * Converts an expiry into the TTL (in seconds) apcu_store() expects.
     */
    private function ttlSeconds(?DateTime $expiry): int
    {
        // APCu's own convention: 0 means "never expire".
        return $expiry === null ? 0 : max(1, $expiry->getTimestamp() - time());
    }
}
