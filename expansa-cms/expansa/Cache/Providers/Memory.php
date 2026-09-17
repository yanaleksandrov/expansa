<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Concerns\Locks;
use Expansa\Cache\Contracts\Provider;

/**
 * A cache provider backed by a plain array — the fastest possible backend, since there is no
 * serialization and no I/O, at the cost of not surviving past the current process/request.
 */
class Memory implements Provider
{
    use Locks;

    /**
     * Cap on entries per group — without it, a long-running process (Scheduler::work(), a bulk
     * export) that touches many distinct keys grows this cache without bound. Evicts the oldest
     * entry (FIFO, not true LRU) once a group hits this size.
     */
    private const int MAX_ENTRIES_PER_GROUP = 5000;

    /**
     * @var array<string, array<string, array{value: mixed, expiry: int|null}>>
     */
    private static array $cache = [];

    /**
     * $expiry accepts an absolute DateTime or a relative time string (e.g. "+1 day").
     */
    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): mixed
    {
        if ($this->isLocked($group, $key)) {
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
     */
    #[\Override]
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
     */
    #[\Override]
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
     */
    #[\Override]
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
     */
    #[\Override]
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }
}
