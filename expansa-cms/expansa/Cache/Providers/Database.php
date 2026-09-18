<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Concerns\Locks;
use Expansa\Cache\Concerns\Memoizes;
use Expansa\Cache\Concerns\Serializes;
use Expansa\Cache\Contracts\Provider;
use Expansa\Facades\Db;

/**
 * A cache provider backed by the `cache` DB table, for entries that must survive past a single
 * request/process — unlike Memory, which is wiped once the PHP process holding it ends.
 *
 * The table has no `group` column, so $group and $key are folded into one physical key
 * ("$group:$key") for storage and lookup. A request-local L1 memo (see Memoizes) sits in front
 * of the table: a key read twice in the same request costs one query, not two — the slowest of
 * the persistent backends benefits from this the most.
 */
class Database implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    private const string TABLE = 'cache';

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

        $row = $this->fetchRow($key, $group);

        if ($row !== null) {
            return $this->memoize($group, $key, $row['value']);
        }

        // An expiry already in the past is never actually stored (or memoized), matching every
        // other provider's behavior of an add()'d-then-immediately-expired entry never being
        // visible to a later get().
        if ($expiry === null || $expiry->getTimestamp() > time()) {
            $this->persist($key, $group, $value, $expiry);
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
        $this->persist($key, $group, $value, null);

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

        $row = $this->fetchRow($key, $group);

        if ($row !== null) {
            return $this->memoize($group, $key, $row['value']);
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
            Db::delete(self::TABLE, ['key' => $this->physicalKey($key, $group)]);
            $this->forgetMemoized($group, $key);
        } else {
            Db::delete(self::TABLE, ['key[~]' => $group . ':%']);
            $this->forgetMemoizedGroup($group);
        }

        return true;
    }

    /**
     * Increases the value of a key by a given amount. Expiry, if any, is preserved.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key === '') {
            return false;
        }

        $row = $this->fetchRow($key, $group);

        if ($row === null || ! is_numeric($row['value'])) {
            return false;
        }

        $value = $row['value'] + $amount;

        $this->persist($key, $group, $value, $row['expiry']);
        $this->memoize($group, $key, $value);

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

    /**
     * Deletes every already-expired row regardless of group. Not called automatically — wire it
     * into a Scheduler job (see Memory::MAX_ENTRIES_PER_GROUP's doc comment for the equivalent
     * concern on the in-memory side) if the table is expected to accumulate expired entries
     * between reads.
     */
    public function purgeExpired(): void
    {
        Db::delete(self::TABLE, [
            'expiry_at[!]'  => null,
            'expiry_at[<=]' => Db::raw('NOW()'),
        ]);
    }

    /**
     * Reads a row and decodes its value, transparently deleting and returning null if it has
     * already expired.
     *
     * @return array{value: mixed, expiry: DateTime|null}|null
     */
    private function fetchRow(string $key, string $group): ?array
    {
        $physicalKey = $this->physicalKey($key, $group);
        $row         = Db::get(self::TABLE, ['value', 'expiry_at'], ['key' => $physicalKey]);

        if ($row === null) {
            return null;
        }

        if ($row['expiry_at'] !== null && strtotime($row['expiry_at']) <= time()) {
            Db::delete(self::TABLE, ['key' => $physicalKey]);

            return null;
        }

        return [
            'value'  => $this->unserializeValue(base64_decode($row['value'])),
            'expiry' => $row['expiry_at'] !== null ? new DateTime($row['expiry_at']) : null,
        ];
    }

    /**
     * Inserts or updates the row for $key/$group. There is no upsert support in the query
     * builder, so existence is checked explicitly first.
     */
    private function persist(string $key, string $group, mixed $value, ?DateTime $expiry): void
    {
        $physicalKey = $this->physicalKey($key, $group);
        $data        = [
            // base64'd on top of serializeValue()'s own encoding: igbinary's output (unlike
            // plain serialize()'s) is raw binary — embedded NUL bytes, non-UTF8 sequences — and
            // the `value` column is a charset-validated MEDIUMTEXT, not a binary/BLOB column.
            // Storing that raw binary directly gets silently mangled by MySQL's charset
            // conversion; base64 keeps the payload pure ASCII so no column type change is needed.
            'value'     => base64_encode($this->serializeValue($value)),
            'expiry_at' => $expiry?->format('Y-m-d H:i:s'),
        ];

        if (Db::get(self::TABLE, ['key'], ['key' => $physicalKey]) !== null) {
            Db::update(self::TABLE, $data, ['key' => $physicalKey]);
        } else {
            Db::insert(self::TABLE, $data + ['key' => $physicalKey]);
        }
    }

    /**
     * The physical row key for $key/$group — the table has no separate group column.
     */
    private function physicalKey(string $key, string $group): string
    {
        return $group . ':' . $key;
    }
}
