<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits;
use Expansa\Facades\Db;

/**
 * A cache provider backed by the `cache` DB table, for entries that must survive past a single
 * request/process — unlike Memory, which is wiped once the PHP process holding it ends.
 *
 * The table has no `group` column, so $group and $key are folded into one physical key
 * ("$group:$key") for storage and lookup.
 */
class Database implements Provider
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

        $row = $this->fetchRow($key, $group);

        if ($row !== null) {
            return $row['value'];
        }

        $this->persist($key, $group, $value, $expiry);

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group. The value never expires — use add()
     * with an $expiry for a TTL-bound entry.
     */
    public function set(string $key, mixed $value, string $group = 'default'): mixed
    {
        $this->persist($key, $group, $value, null);

        return $value;
    }

    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        $row = $this->fetchRow($key, $group);

        if ($row !== null) {
            return $row['value'];
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
            Db::delete(self::$table, ['key' => $this->physicalKey($key, $group)]);
        } else {
            Db::delete(self::$table, ['key[~]' => $group . ':%']);
        }

        return true;
    }

    /**
     * Increases the value of a key by a given amount. Expiry, if any, is preserved.
     */
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key === '') {
            return false;
        }

        $row = $this->fetchRow($key, $group);

        if ($row === null || ! is_numeric($row['value'])) {
            return false;
        }

        $this->persist($key, $group, $row['value'] + $amount, $row['expiry']);

        return true;
    }

    /**
     * Decreases the value of a key by a given amount. Expiry, if any, is preserved.
     */
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
        Db::delete(self::$table, [
            'expiry_at[!]'  => null,
            'expiry_at[<=]' => Db::raw('NOW()'),
        ]);
    }

    /**
     * Reads a row and unserializes its value, transparently deleting and returning null if it has
     * already expired.
     *
     * @return array{value: mixed, expiry: DateTime|null}|null
     */
    private function fetchRow(string $key, string $group): ?array
    {
        $physicalKey = $this->physicalKey($key, $group);
        $row         = Db::get(self::$table, ['value', 'expiry_at'], ['key' => $physicalKey]);

        if ($row === null) {
            return null;
        }

        if ($row['expiry_at'] !== null && strtotime($row['expiry_at']) <= time()) {
            Db::delete(self::$table, ['key' => $physicalKey]);

            return null;
        }

        return [
            'value'  => unserialize($row['value']),
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
            'value'     => serialize($value),
            'expiry_at' => $expiry?->format('Y-m-d H:i:s'),
        ];

        if (Db::get(self::$table, ['key'], ['key' => $physicalKey]) !== null) {
            Db::update(self::$table, $data, ['key' => $physicalKey]);
        } else {
            Db::insert(self::$table, $data + ['key' => $physicalKey]);
        }
    }

    private function physicalKey(string $key, string $group): string
    {
        return $group . ':' . $key;
    }
}
