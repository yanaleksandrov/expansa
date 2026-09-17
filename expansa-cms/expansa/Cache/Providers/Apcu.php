<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits;

/**
 * A cache provider backed by APCu — shared memory local to this machine, visible to every
 * PHP-FPM worker process (unlike Memory, which only lives inside a single process/request).
 * Requires ext-apcu; no server, connection or serialization step needed.
 *
 * APCu has no key-enumeration call cheap enough to rely on, so a whole-group forget() uses the
 * same version-counter trick as Memcached: clearing a group bumps a counter baked into the
 * physical key, orphaning old entries instead of deleting them (they expire/evict on their own).
 */
class Apcu implements Provider
{
    use Traits;

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
        $existing    = apcu_fetch($physicalKey, $success);

        if ($success) {
            return $existing;
        }

        // An expiry already in the past is never actually stored, matching Memory's behavior of
        // an add()'d-then-immediately-expired entry never being visible to a later get().
        if ($expiry === null || $expiry->getTimestamp() > time()) {
            apcu_store($physicalKey, $value, $this->ttlSeconds($expiry));
        }

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group. The value never expires — use add()
     * with an $expiry for a TTL-bound entry.
     */
    public function set(string $key, mixed $value, string $group = 'default'): mixed
    {
        apcu_store($this->physicalKey($key, $group), $value);

        return $value;
    }

    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        $value = apcu_fetch($this->physicalKey($key, $group), $success);

        if ($success) {
            return $value;
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
            apcu_delete($this->physicalKey($key, $group));

            return true;
        }

        $versionKey = $this->versionKey($group);

        // apcu_inc() would auto-create a missing key at 0 then add the step, landing on the same
        // value groupVersion() already assumes by default — old keys wouldn't be orphaned. Only
        // increment an existing counter; otherwise jump straight to 2 to guarantee a bump.
        if (apcu_exists($versionKey)) {
            apcu_inc($versionKey);
        } else {
            apcu_store($versionKey, 2);
        }

        return true;
    }

    /**
     * Increases the value of a key by a given amount. APCu exposes no way to read a key's
     * remaining TTL, so unlike Memory/Database/Redis, any existing expiry is not preserved here.
     */
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

        apcu_store($physicalKey, $value + $amount);

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
        $version = apcu_fetch($this->versionKey($group), $success);

        return $success ? (int) $version : 1;
    }

    private function versionKey(string $group): string
    {
        return "__version__:$group";
    }

    private function ttlSeconds(?DateTime $expiry): int
    {
        // APCu's own convention: 0 means "never expire".
        return $expiry === null ? 0 : max(1, $expiry->getTimestamp() - time());
    }
}
