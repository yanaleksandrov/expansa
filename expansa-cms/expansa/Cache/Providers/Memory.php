<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits\Locks;

/**
 * Plain array storage: the fastest backend, no serialization or I/O, but lives only until the process ends.
 * The target of the Cache facade.
 *
 * @package Expansa\Cache\Providers
 */
final class Memory implements Provider
{
    use Locks;

    /**
     * Entries per group, then the oldest one is dropped: a long-running process must not grow without bound.
     */
    private const int MAX_ENTRIES_PER_GROUP = 5000;

    /**
     * Entries by group and key, expiry is a timestamp.
     *
     * @var array<string, array<string, array{value: mixed, expiry: int|null}>>
     */
    private static array $cache = [];

    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): bool
    {
        if ($this->isLocked($group, $key)) {
            return false;
        }

        $entry = self::$cache[$group][$key] ?? null;
        if ($entry !== null) {
            if ($entry['expiry'] === null || $entry['expiry'] > time()) {
                return false;
            }
            unset(self::$cache[$group][$key]);
        }

        $expiry = is_string($expiry) ? new DateTime($expiry)->getTimestamp() : $expiry?->getTimestamp();
        if ($expiry !== null && $expiry <= time()) {
            return false;
        }

        $this->evict($group);

        self::$cache[$group][$key] = ['value' => $value, 'expiry' => $expiry];

        return true;
    }

    #[\Override]
    public function set(string $key, mixed $value, string $group = 'default'): bool
    {
        if (! isset(self::$cache[$group][$key])) {
            $this->evict($group);
        }

        self::$cache[$group][$key] = ['value' => $value, 'expiry' => null];

        return true;
    }

    #[\Override]
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        $entry = self::$cache[$group][$key] ?? null;
        if ($entry !== null) {
            if ($entry['expiry'] === null || $entry['expiry'] > time()) {
                return $entry['value'];
            }
            unset(self::$cache[$group][$key]);
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
            unset(self::$cache[$group][$key]);
        } else {
            unset(self::$cache[$group]);
        }

        return true;
    }

    /**
     * Keeps the expiry of the value.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        $entry = self::$cache[$group][$key] ?? null;
        if ($entry === null || ! is_numeric($entry['value']) || ($entry['expiry'] !== null && $entry['expiry'] <= time())) {
            return false;
        }

        self::$cache[$group][$key]['value'] += $amount;

        return true;
    }

    #[\Override]
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }

    private function evict(string $group): void
    {
        if (count(self::$cache[$group] ?? []) >= self::MAX_ENTRIES_PER_GROUP) {
            unset(self::$cache[$group][array_key_first(self::$cache[$group])]);
        }
    }
}
