<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits\Locks;
use Expansa\Cache\Traits\Memoizes;

/**
 * APCu (ext-apcu): shared memory of the machine, visible to every PHP-FPM worker, no server or serialization.
 *
 * APCu can't cheaply list keys, so forgetting a group bumps its version, which is part of every
 * physical key: old entries are orphaned and expire or get evicted on their own. Values and group
 * versions are memoized for the request.
 *
 * @package Expansa\Cache\Providers
 */
final class Apcu implements Provider
{
    use Locks;
    use Memoizes;

    /**
     * Group versions read in this request.
     *
     * @var array<string, int>
     */
    private static array $groupVersions = [];

    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): bool
    {
        if ($this->isLocked($group, $key) || $this->hasMemoized($group, $key)) {
            return false;
        }

        $physicalKey = $this->physicalKey($key, $group);
        $existing    = apcu_fetch($physicalKey, $success);
        if ($success) {
            $this->memoize($group, $key, $existing);

            return false;
        }

        $expiry = is_string($expiry) ? new DateTime($expiry)->getTimestamp() : $expiry?->getTimestamp();
        if ($expiry !== null && $expiry <= time()) {
            return false;
        }

        // 0 never expires
        apcu_store($physicalKey, $value, $expiry === null ? 0 : max(1, $expiry - time()));

        $this->memoize($group, $key, $value);

        return true;
    }

    #[\Override]
    public function set(string $key, mixed $value, string $group = 'default'): bool
    {
        apcu_store($this->physicalKey($key, $group), $value);
        $this->memoize($group, $key, $value);

        return true;
    }

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
            apcu_delete($this->physicalKey($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        $versionKey = $this->versionKey($group);

        // apcu_inc() would create a missing counter at 1, the version groupVersion() already assumes
        if (apcu_exists($versionKey)) {
            $version = (int) apcu_inc($versionKey);
        } else {
            $version = 2;
            apcu_store($versionKey, $version);
        }

        self::$groupVersions[$group] = $version;
        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * APCu can't read the remaining TTL, so the expiry of the value is lost.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
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
        if (! isset(self::$groupVersions[$group])) {
            $version = apcu_fetch($this->versionKey($group), $success);

            self::$groupVersions[$group] = $success ? (int) $version : 1;
        }

        return self::$groupVersions[$group];
    }

    private function versionKey(string $group): string
    {
        return "__version__:$group";
    }
}
