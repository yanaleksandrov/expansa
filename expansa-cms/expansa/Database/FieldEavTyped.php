<?php

declare(strict_types=1);

namespace Expansa\Database;

use DateTime;
use Expansa\Database\Query\Builder;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;

/**
 * A typed sibling of {@see FieldEav}, not a replacement — reused via extends (constructor,
 * $fieldsForeignKey, $ownerId, cached(), isEmpty(), mutate()). Routes each key by its PHP type
 * into "{fieldsForeignTable}_int/decimal/datetime/varchar/text" instead of one MEDIUMTEXT column,
 * trading slower point CRUD (measured 1.4-3.5x) for indexed, correct filter/sort/search (18-40x).
 *
 * int and decimal get separate tables rather than sharing one with two nullable columns: BIGINT's
 * full 64-bit range wouldn't fit DECIMAL(20,6)'s 14 integer digits, and DECIMAL's fractional part
 * has no BIGINT equivalent - one shared numeric column would have to lose one or the other. Two
 * single-value tables also keep every table here the same shape ([fk, key, value]), so nothing
 * downstream needs to special-case which columns a given row actually populated.
 */
class FieldEavTyped extends FieldEav
{
    private const TABLES = ['int', 'decimal', 'datetime', 'varchar', 'text'];

    /**
     * Physical name of one of the five typed tables, e.g. "users_fields_int" — built off the
     * inherited $fieldsForeignTable ("users_fields"), which here is a shared prefix rather than a
     * table of its own.
     *
     * @param string $suffix One of self::TABLES.
     * @return string
     */
    private function table(string $suffix): string
    {
        return "{$this->fieldsForeignTable}_$suffix";
    }

    /**
     * Cache group, distinct from Field's own $fieldsForeignTable: without this, both stores would
     * overwrite each other's cached data in Memory's process-local array the moment they're both
     * used for the same owner in one request.
     *
     * @var string
     */
    private string $cacheGroup {
        get => $this->cacheGroup ??= "{$this->fieldsForeignTable}_typed";
    }

    /**
     * Overrides the inherited {@see FieldEav::cached()} only to key it by $cacheGroup instead of
     * $fieldsForeignTable — same behavior otherwise.
     *
     * @return array<string, array<int, mixed>>|null
     */
    protected function cached(): ?array
    {
        return Cache::get("$this->ownerId", $this->cacheGroup);
    }

    /**
     * Which of the five tables a value belongs in, and the value cast to that table's single
     * "value" column.
     *
     * Values longer than {@see Builder::MAX_INDEXABLE_LENGTH} go to the text/FULLTEXT table
     * instead of varchar - the same ceiling {@see \Expansa\Database\Schema\Compilers\Indexes}
     * already designs around, rather than inventing a second threshold. Compared in *characters*
     * (mb_strlen), not bytes (strlen): MAX_INDEXABLE_LENGTH and the varchar column it matches are
     * both declared in characters (MySQL's own VARCHAR(n) semantics), and utf8mb4 characters are
     * 1-4 bytes each - strlen() would hit the limit on non-Latin text (Cyrillic, CJK, ...) well
     * before it actually reached that many characters, routing it to the text table needlessly
     * early.
     *
     * @param mixed $value
     * @return array{0: string, 1: mixed} [table suffix, cast value]
     */
    private function route(mixed $value): array
    {
        return match (true) {
            is_int($value),
            is_bool($value)                                                    => ['int', (int) $value],
            is_float($value)                                                   => ['decimal', $value],
            $value instanceof DateTime                                         => ['datetime', $value->format('Y-m-d H:i:s')],
            mb_strlen((string) $value, 'UTF-8') <= Builder::MAX_INDEXABLE_LENGTH => ['varchar', (string) $value],
            default                                                            => ['text', (string) $value],
        };
    }

    /**
     * One insert row for $key/$value - every typed table shares the same [fk, key, value] shape,
     * so there's nothing left to special-case per table (unlike the old shared scalar table,
     * which needed one of two columns nulled out depending on which type actually applied).
     *
     * @param string $key
     * @param mixed  $value Already cast by {@see self::route()}.
     * @return array<string, mixed>
     */
    private function row(string $key, mixed $value): array
    {
        return [$this->fieldsForeignKey => $this->ownerId, 'key' => $key, 'value' => $value];
    }

    /**
     * Retrieves the value(s) of a field, or every field, for the owner — same shape and
     * cache-then-query behavior as {@see FieldEav::find()}, just sourced from five tables instead
     * of one (merged in PHP; there's no single index that spans all five).
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
                $rows = Db::select($this->table($suffix), ['key', 'value'], [
                    $this->fieldsForeignKey => $this->ownerId,
                ]);

                foreach ($rows ?? [] as $row) {
                    $grouped[$row['key']][] = $row['value'];
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
     * {@see FieldEav::add()}, including reusing find()'s warm cache (inherited cached()) for the
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

            [$suffix, $cast]    = $this->route($value);
            $byTable[$suffix][] = [$key, $cast];
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

            $rows = array_map(fn($i) => $this->row($i[0], $i[1]), $items);

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
     * {@see FieldEav::update()}; a type change costs up to four extra, empty DELETEs.
     *
     * @param string $key      The field key to update.
     * @param mixed  $value    The new value.
     * @param mixed  $oldValue Unused — kept for signature compatibility with {@see FieldEav::update()}.
     * @return bool True if a row was changed.
     */
    public function update(string $key, mixed $value, mixed $oldValue = ''): bool
    {
        if (! $this->ownerId) {
            return false;
        }

        [$targetSuffix, $cast] = $this->route($value);

        $result = Db::update($this->table($targetSuffix), ['value' => $cast], [
            'key' => $key, $this->fieldsForeignKey => $this->ownerId,
        ]);

        if ($result && $result->rowCount() > 0) {
            Cache::forget("$this->ownerId", $this->cacheGroup);
            return true;
        }

        // Not in the routed table: either the key doesn't exist, or its type changed. Clear it
        // from the other four (only one could hold it) then insert fresh into the routed one.
        foreach (self::TABLES as $suffix) {
            if ($suffix !== $targetSuffix) {
                Db::delete($this->table($suffix), ['key' => $key, $this->fieldsForeignKey => $this->ownerId]);
            }
        }

        $result = Db::insert($this->table($targetSuffix), $this->row($key, $cast));

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

        $tables    = self::TABLES;
        $valueCast = null;

        if ($value !== '') {
            [$targetSuffix, $valueCast] = $this->route($value);
            $tables = [$targetSuffix];
        }

        $deletedAny = false;
        foreach ($tables as $suffix) {
            $conditions = [$this->fieldsForeignKey => $this->ownerId];
            if ($key !== '') {
                $conditions['key'] = $key;
            }
            if ($valueCast !== null) {
                $conditions['value'] = $valueCast;
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
     * One UPDATE...CASE per chunk for a single table's "value" column — same pattern as
     * {@see FieldEav::import()}'s own batched update, just parameterized so import() can reuse it
     * once per suffix instead of inlining it once per table.
     *
     * @param array<string, mixed> $keyed key => new value, all destined for the same table.
     * @return int Total rows changed across all chunks.
     */
    private function batchUpdate(string $table, array $keyed, int $chunkSize): int
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

            $sql = "UPDATE <$table> SET value = CASE `key` " . implode(' ', $cases) . ' END'
                . ' WHERE `key` IN (' . implode(', ', $placeholders) . ") AND `$this->fieldsForeignKey` = :owner_id";

            $rows = Db::query($sql, $params);
            $updated += $rows ? $rows->rowCount() : 0;
        }

        return $updated;
    }

    /**
     * Bulk-syncs the owner's fields to exactly match $fields — same contract as
     * {@see FieldEav::import()} — grouped by routed table first so each gets its own chunked
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
        foreach ($routed as $key => [$suffix, $cast]) {
            $byTable[$suffix][$key] = $cast;
        }

        // Changed values, grouped by suffix — batched into UPDATE...CASE below instead of one
        // Db::update() per key, same reasoning as Field::import()'s own UPDATE...CASE pass.
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
                    $rows = Db::select($this->table($suffix), ['key', 'value'], [
                        $this->fieldsForeignKey => $this->ownerId,
                        'key'                   => $chunkKeys,
                    ]);

                    foreach ($rows ?? [] as $row) {
                        $existing[$row['key']] ??= $row['value'];
                    }
                }

                $toInsert = [];
                foreach ($chunkKeys as $key) {
                    $cast = $routed[$key][1];

                    if (! array_key_exists($key, $existing)) {
                        $toInsert[] = $this->row($key, $cast);
                    } elseif ((string) $existing[$key] !== (string) $cast) {
                        $toUpdate[$suffix][$key] = $cast;
                    }
                }

                if ($toInsert) {
                    $rows = Db::insert($this->table($suffix), $toInsert);
                    $result['inserted'] += $rows ? $rows->rowCount() : 0;
                }
            }
        }

        foreach ($toUpdate as $suffix => $keyed) {
            $result['updated'] += $this->batchUpdate($this->table($suffix), $keyed, $chunkSize);
        }

        // Delete anything not present in $fields, across all five tables — same "single
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
