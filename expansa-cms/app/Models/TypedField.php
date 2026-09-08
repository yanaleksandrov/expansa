<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;

/**
 * A typed sibling of {@see Field}, not a replacement — reused via extends (constructor,
 * $fieldsForeignKey, $ownerId, cached(), isEmpty(), mutate()). Routes each key by its PHP type
 * into "{fieldsForeignTable}_scalar/datetime/varchar/text" instead of one MEDIUMTEXT column,
 * trading slower point CRUD (measured 1.4-3.5x) for indexed, correct filter/sort/search (18-40x).
 */
class TypedField extends Field
{
    private const TABLES = ['scalar', 'datetime', 'varchar', 'text'];

    /**
     * Values longer than this go to the text/FULLTEXT table instead of varchar — matches
     * EX_DB_MAX_INDEX_LENGTH, the same utf8mb4 index-byte-length ceiling the rest of the app
     * already designs around, rather than inventing a second threshold.
     */
    private const VARCHAR_MAX_LENGTH = EX_DB_MAX_INDEX_LENGTH;

    /**
     * Physical name of one of the four typed tables, e.g. "users_fields_scalar" — built off the
     * inherited $fieldsForeignTable ("users_fields"), which here is a shared prefix rather than a
     * table of its own.
     */
    private function table(string $suffix): string
    {
        return "{$this->fieldsForeignTable}_$suffix";
    }

    /**
     * Cache group, distinct from Field's own $fieldsForeignTable: without this, both stores would
     * overwrite each other's cached data in Memory's process-local array the moment they're both
     * used for the same owner in one request.
     */
    private string $cacheGroup {
        get => $this->cacheGroup ??= "{$this->fieldsForeignTable}_typed";
    }

    /**
     * Overrides the inherited {@see Field::cached()} only to key it by $cacheGroup instead of
     * $fieldsForeignTable — same behavior otherwise.
     *
     * @return array<string, array<int, mixed>>|null
     */
    protected function cached(): ?array
    {
        return Cache::get("$this->ownerId", $this->cacheGroup);
    }

    /**
     * Which of the four tables a value belongs in, which column holds it there, and the value
     * cast to that column's shape.
     *
     * @return array{0: string, 1: string, 2: mixed} [table suffix, value column, cast value]
     */
    private function route(mixed $value): array
    {
        return match (true) {
            is_int($value),
            is_bool($value)                                     => ['scalar', 'value_int', (int) $value],
            is_float($value)                                    => ['scalar', 'value_decimal', $value],
            $value instanceof DateTime                          => ['datetime', 'value', $value->format('Y-m-d H:i:s')],
            strlen((string) $value) <= self::VARCHAR_MAX_LENGTH => ['varchar', 'value', (string) $value],
            default                                             => ['text', 'value', (string) $value],
        };
    }

    /**
     * One insert row for $suffix/$column/$value. The scalar table always gets both value_int and
     * value_decimal (the unused one null) — a multi-row Db::insert() builds its column list off
     * the first row, so a batch mixing int-only and decimal-only rows would silently write NULL
     * into whichever column that first row didn't have, instead of what each row actually meant.
     */
    private function row(string $suffix, string $key, string $column, mixed $value): array
    {
        $row = [$this->fieldsForeignKey => $this->ownerId, 'key' => $key];

        if ($suffix === 'scalar') {
            $row['value_int']     = $column === 'value_int' ? $value : null;
            $row['value_decimal'] = $column === 'value_decimal' ? $value : null;
        } else {
            $row[$column] = $value;
        }

        return $row;
    }

    /**
     * Retrieves the value(s) of a field, or every field, for the owner — same shape and
     * cache-then-query behavior as {@see Field::find()}, just sourced from four tables instead
     * of one (merged in PHP; there's no single index that spans all four).
     *
     * @param string $key      Field key to retrieve. Empty returns every field, grouped by key.
     * @param bool   $isSingle Return only the first value for $key instead of the full list.
     * @return mixed The field value(s), the full grouped array, or null if the owner has no id yet.
     */
    public function find(string $key = '', bool $isSingle = true): mixed
    {
        if (! $this->ownerId) {
            return null;
        }

        $fields = Cache::get("$this->ownerId", $this->cacheGroup, function () {
            $grouped = [];

            foreach (self::TABLES as $suffix) {
                $columns = $suffix === 'scalar' ? ['key', 'value_int', 'value_decimal'] : ['key', 'value'];
                $rows    = Db::select($this->table($suffix), $columns, [
                    $this->fieldsForeignKey => $this->ownerId,
                ]);

                foreach ($rows ?? [] as $row) {
                    $value = $suffix === 'scalar' ? ($row['value_int'] ?? $row['value_decimal']) : $row['value'];
                    $grouped[$row['key']][] = $value;
                }
            }

            return $grouped ?: null;
        });

        if (! is_array($fields)) {
            return $key === '' ? [] : null;
        }

        if ($key === '') {
            return $fields;
        }

        return $isSingle ? ($fields[$key][0] ?? null) : ($fields[$key] ?? null);
    }

    /**
     * Adds one or more new field values for the owner, routing each into its typed table. A key
     * that already has a value is skipped when $isUnique (the default) — same semantics as
     * {@see Field::add()}, including reusing find()'s warm cache (inherited cached()) for the
     * uniqueness check when it's already loaded.
     *
     * @param array<string, mixed> $attributes Key/value pairs to add.
     * @param bool                 $isUnique   Skip keys that already have a value.
     * @return bool True if at least one row was inserted.
     */
    public function add(array $attributes, bool $isUnique = true): bool
    {
        if (! $this->ownerId) {
            return false;
        }

        $cached = $isUnique ? $this->cached() : null;

        $byTable = [];
        foreach ($attributes as $key => $value) {
            if ($this->isEmpty($value)) {
                continue;
            }
            if ($isUnique && $cached !== null && array_key_exists($key, $cached)) {
                continue;
            }

            [$suffix, $column, $cast] = $this->route($value);
            $byTable[$suffix][]       = [$key, $column, $cast];
        }

        if (! $byTable) {
            return false;
        }

        // Cold-cache uniqueness check: one narrow SELECT per table actually touched, scoped to
        // exactly the keys headed there — never a full-table pull. Same principle as Field::add().
        if ($isUnique && $cached === null) {
            foreach ($byTable as $suffix => $items) {
                $keys     = array_column($items, 0);
                $existing = array_flip(Db::select($this->table($suffix), 'key', [
                    $this->fieldsForeignKey => $this->ownerId,
                    'key'                   => $keys,
                ]) ?? []);

                $byTable[$suffix] = array_values(array_filter($items, fn($i) => ! isset($existing[$i[0]])));
            }
        }

        $insertedAny = false;
        foreach ($byTable as $suffix => $items) {
            if (! $items) {
                continue;
            }

            $rows = array_map(fn($i) => $this->row($suffix, $i[0], $i[1], $i[2]), $items);

            $result      = Db::insert($this->table($suffix), $rows);
            $insertedAny = $insertedAny || ($result && $result->rowCount() > 0);
        }

        if ($insertedAny) {
            Cache::forget("$this->ownerId", $this->cacheGroup);
        }

        return $insertedAny;
    }

    /**
     * Updates an existing field value, moving it to a different table if the new value's type
     * changed (was an int, now a string, ...). Same type as before costs one UPDATE, like
     * {@see Field::update()}; a type change costs up to three extra, empty DELETEs.
     *
     * @param string $key      The field key to update.
     * @param mixed  $value    The new value.
     * @param mixed  $oldValue Unused — kept for signature compatibility with {@see Field::update()}.
     * @return bool True if a row was changed.
     */
    public function update(string $key, mixed $value, mixed $oldValue = ''): bool
    {
        if (! $this->ownerId) {
            return false;
        }

        [$targetSuffix, $column, $cast] = $this->route($value);

        $result = Db::update($this->table($targetSuffix), [$column => $cast], [
            'key' => $key, $this->fieldsForeignKey => $this->ownerId,
        ]);

        if ($result && $result->rowCount() > 0) {
            Cache::forget("$this->ownerId", $this->cacheGroup);
            return true;
        }

        // Not in the routed table: either the key doesn't exist, or its type changed. Clear it
        // from the other three (only one could hold it) then insert fresh into the routed one.
        foreach (self::TABLES as $suffix) {
            if ($suffix !== $targetSuffix) {
                Db::delete($this->table($suffix), ['key' => $key, $this->fieldsForeignKey => $this->ownerId]);
            }
        }

        $result = Db::insert($this->table($targetSuffix), $this->row($targetSuffix, $key, $column, $cast));

        Cache::forget("$this->ownerId", $this->cacheGroup);

        return (bool) $result;
    }

    /**
     * Deletes field(s) for the owner. Without a type registry this fans out to one DELETE per
     * table (at most one ever matches) — unless $value is given, whose type pins it to exactly
     * one table via route(), narrowing this to a single DELETE.
     *
     * @param string $key   Restrict to this field key; empty deletes everything for the owner.
     * @param mixed  $value Restrict to this specific value.
     * @return bool True if a row was removed.
     */
    public function delete(string $key = '', mixed $value = ''): bool
    {
        if (! $this->ownerId) {
            return false;
        }

        $tables      = self::TABLES;
        $valueColumn = null;
        $valueCast   = null;

        if ($value !== '') {
            [$targetSuffix, $valueColumn, $valueCast] = $this->route($value);
            $tables = [$targetSuffix];
        }

        $deletedAny = false;
        foreach ($tables as $suffix) {
            $conditions = [$this->fieldsForeignKey => $this->ownerId];
            if ($key !== '') {
                $conditions['key'] = $key;
            }
            if ($valueColumn !== null) {
                $conditions[$valueColumn] = $valueCast;
            }

            $result     = Db::delete($this->table($suffix), $conditions);
            $deletedAny = $deletedAny || ($result && $result->rowCount() > 0);
        }

        if ($deletedAny) {
            Cache::forget("$this->ownerId", $this->cacheGroup);
        }

        return $deletedAny;
    }

    /**
     * One UPDATE...CASE per chunk for a single (table, column) pair — same pattern as
     * {@see Field::import()}'s own batched update, just parameterized so import() can reuse it
     * once per suffix/column combination instead of inlining it four times over.
     *
     * @param array<string, mixed> $keyed key => new value, all destined for the same column.
     * @return int Total rows changed across all chunks.
     */
    private function batchUpdate(string $table, string $column, array $keyed, int $chunkSize): int
    {
        $updated = 0;

        foreach (array_chunk($keyed, $chunkSize, true) as $chunk) {
            $cases        = [];
            $placeholders = [];
            $params       = ['owner_id' => $this->ownerId];
            $n            = 0;

            foreach ($chunk as $key => $value) {
                $cases[]        = "WHEN :key_$n THEN :value_$n";
                $placeholders[] = ":key_$n";

                $params[":key_$n"]   = $key;
                $params[":value_$n"] = $value;

                $n++;
            }

            $sql = "UPDATE <$table> SET `$column` = CASE `key` " . implode(' ', $cases) . ' END'
                . ' WHERE `key` IN (' . implode(', ', $placeholders) . ") AND `$this->fieldsForeignKey` = :owner_id";

            $rows = Db::query($sql, $params);
            $updated += $rows ? $rows->rowCount() : 0;
        }

        return $updated;
    }

    /**
     * Bulk-syncs the owner's fields to exactly match $fields — same contract as
     * {@see Field::import()} — grouped by routed table first so each gets its own chunked
     * SELECT/INSERT/UPDATE pass (skipped when find()'s cache is already warm).
     *
     * @param array<string, mixed> $fields
     * @param int                  $chunkSize
     * @return array{deleted: int, inserted: int, updated: int}
     */
    public function import(array $fields, int $chunkSize = 1000): array
    {
        $result = ['deleted' => 0, 'inserted' => 0, 'updated' => 0];

        if (! $this->ownerId) {
            return $result;
        }

        $fields = array_filter(
            $fields,
            fn($value, $key) => is_string($key) && ! $this->isEmpty($value),
            ARRAY_FILTER_USE_BOTH
        );

        $routed = [];
        foreach ($fields as $key => $value) {
            $routed[$key] = $this->route($value);
        }

        $cached = $this->cached();

        $byTable = [];
        foreach ($routed as $key => [$suffix, , $cast]) {
            $byTable[$suffix][$key] = $cast;
        }

        // Changed values, grouped by [suffix][column] — batched into UPDATE...CASE below instead
        // of one Db::update() per key, same reasoning as Field::import()'s own UPDATE...CASE pass.
        $toUpdate = [];

        foreach ($byTable as $suffix => $keyed) {
            foreach (array_chunk(array_keys($keyed), $chunkSize) as $chunkKeys) {
                $existing = [];

                if ($cached !== null) {
                    foreach ($chunkKeys as $key) {
                        if (array_key_exists($key, $cached)) {
                            $existing[$key] = $cached[$key][0];
                        }
                    }
                } else {
                    $columns = $suffix === 'scalar' ? ['key', 'value_int', 'value_decimal'] : ['key', 'value'];
                    $rows    = Db::select($this->table($suffix), $columns, [
                        $this->fieldsForeignKey => $this->ownerId,
                        'key'                   => $chunkKeys,
                    ]);

                    foreach ($rows ?? [] as $row) {
                        $existing[$row['key']] ??= $suffix === 'scalar'
                            ? ($row['value_int'] ?? $row['value_decimal'])
                            : $row['value'];
                    }
                }

                $toInsert = [];
                foreach ($chunkKeys as $key) {
                    [, $column, $cast] = $routed[$key];

                    if (! array_key_exists($key, $existing)) {
                        $toInsert[] = $this->row($suffix, $key, $column, $cast);
                    } elseif ((string) $existing[$key] !== (string) $cast) {
                        $toUpdate[$suffix][$column][$key] = $cast;
                    }
                }

                if ($toInsert) {
                    $rows = Db::insert($this->table($suffix), $toInsert);
                    $result['inserted'] += $rows ? $rows->rowCount() : 0;
                }
            }
        }

        foreach ($toUpdate as $suffix => $byColumn) {
            foreach ($byColumn as $column => $keyed) {
                $result['updated'] += $this->batchUpdate($this->table($suffix), $column, $keyed, $chunkSize);
            }
        }

        // Delete anything not present in $fields, across all four tables — same "single
        // server-side NOT IN pass" reasoning as Field::import().
        $keys = array_keys($fields);
        foreach (self::TABLES as $suffix) {
            $conditions = [$this->fieldsForeignKey => $this->ownerId] + ($keys ? ['key[!]' => $keys] : []);
            $rows       = Db::delete($this->table($suffix), ['AND' => $conditions]);
            $result['deleted'] += $rows ? $rows->rowCount() : 0;
        }

        Cache::forget("$this->ownerId", $this->cacheGroup);

        return $result;
    }
}
