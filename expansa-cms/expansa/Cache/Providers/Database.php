<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits\Locks;
use Expansa\Cache\Traits\Memoizes;
use Expansa\Cache\Traits\Serializes;
use Expansa\Database\Query\Builder;

/**
 * The `cache` table: outlives the process. The only provider that depends on the Database package,
 * loaded only when chosen.
 *
 * The table has no group column, so the physical key is "group:key". Values are memoized for the request.
 *
 * @package Expansa\Cache\Providers
 */
final class Database implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    private const string TABLE = 'cache';

    public function __construct(

        /**
         * Connection with the `cache` table.
         */
        private readonly Builder $db,
    ) {}

    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): bool
    {
        if ($this->isLocked($group, $key) || $this->hasMemoized($group, $key)) {
            return false;
        }

        $row = $this->read($key, $group);
        if ($row !== null) {
            $this->memoize($group, $key, $row['value']);

            return false;
        }

        $expiry = is_string($expiry) ? new DateTime($expiry) : $expiry;
        if ($expiry !== null && $expiry->getTimestamp() <= time()) {
            return false;
        }

        $this->write($key, $group, $value, $expiry);
        $this->memoize($group, $key, $value);

        return true;
    }

    #[\Override]
    public function set(string $key, mixed $value, string $group = 'default'): bool
    {
        $this->write($key, $group, $value, null);
        $this->memoize($group, $key, $value);

        return true;
    }

    #[\Override]
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        if ($this->hasMemoized($group, $key)) {
            return $this->memoized($group, $key);
        }

        $row = $this->read($key, $group);
        if ($row !== null) {
            return $this->memoize($group, $key, $row['value']);
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
            $this->db->delete(self::TABLE, ['key' => $this->physicalKey($key, $group)]);
            $this->forgetMemoized($group, $key);
        } else {
            $this->db->delete(self::TABLE, ['key[~]' => $group . ':%']);
            $this->forgetMemoizedGroup($group);
        }

        return true;
    }

    /**
     * Keeps the expiry of the value.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        $row = $this->read($key, $group);
        if ($row === null || ! is_numeric($row['value'])) {
            return false;
        }

        $value = $row['value'] + $amount;

        $this->write($key, $group, $value, $row['expiry']);
        $this->memoize($group, $key, $value);

        return true;
    }

    #[\Override]
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }

    /**
     * Delete expired rows of every group. Not called automatically: expired keys that are never
     * read stay in the table, run it from a scheduled job.
     *
     * @return void
     */
    public function purgeExpired(): void
    {
        $this->db->delete(self::TABLE, [
            'expiry_at[!]'  => null,
            'expiry_at[<=]' => Builder::raw('NOW()'),
        ]);
    }

    /**
     * Read a row and decode its value, an expired row is deleted.
     *
     * @param string $key
     * @param string $group
     * @return array{value: mixed, expiry: DateTime|null}|null
     */
    private function read(string $key, string $group): ?array
    {
        $physicalKey = $this->physicalKey($key, $group);
        $row         = $this->db->get(self::TABLE, ['value', 'expiry_at'], ['key' => $physicalKey]);
        if ($row === null) {
            return null;
        }

        if ($row['expiry_at'] !== null && strtotime($row['expiry_at']) <= time()) {
            $this->db->delete(self::TABLE, ['key' => $physicalKey]);

            return null;
        }

        return [
            'value'  => $this->unserializeValue(base64_decode($row['value'])),
            'expiry' => $row['expiry_at'] !== null ? new DateTime($row['expiry_at']) : null,
        ];
    }

    /**
     * Insert or update the row, the query builder has no upsert.
     *
     * @param string        $key
     * @param string        $group
     * @param mixed         $value
     * @param DateTime|null $expiry
     * @return void
     */
    private function write(string $key, string $group, mixed $value, ?DateTime $expiry): void
    {
        $physicalKey = $this->physicalKey($key, $group);
        $data        = [
            // igbinary output is binary and the column is text: base64 keeps it from charset conversion
            'value'     => base64_encode($this->serializeValue($value)),
            'expiry_at' => $expiry?->format('Y-m-d H:i:s'),
        ];

        if ($this->db->get(self::TABLE, ['key'], ['key' => $physicalKey]) !== null) {
            $this->db->update(self::TABLE, $data, ['key' => $physicalKey]);
        } else {
            $this->db->insert(self::TABLE, $data + ['key' => $physicalKey]);
        }
    }

    private function physicalKey(string $key, string $group): string
    {
        return $group . ':' . $key;
    }
}
