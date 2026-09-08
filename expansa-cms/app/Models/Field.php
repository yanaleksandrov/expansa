<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;

/**
 * A dynamic key/value meta-fields store bound to any Model that `use`s {@see Model\HasFieldEav}
 * (User, Apikey, ...) — one "{owner_table}_fields" table per owner table. Always lazy: built only
 * on first access to the owner's field() accessor, never at construction time. $fieldsForeignTable,
 * $fieldsForeignKey and $ownerId below are hook-only properties that forward straight to $owner.
 */
class Field extends Model
{
    public function __construct(

        /**
         * The Model this store belongs to.
         */
        protected Model $owner
    ) {} // phpcs:ignore

    /**
     * Name of the metadata table for the owner, e.g. "users_fields".
     */
    public string $fieldsForeignTable {
        get => $this->fieldsForeignTable ??= $this->owner->getTable() . '_fields';
    }

    /**
     * Foreign key column on that table pointing back to the owner, e.g. "user_id".
     */
    public string $fieldsForeignKey {
        get => $this->owner->fieldsForeignKey;
    }

    /**
     * Primary key of the owner, or 0 if it hasn't been persisted yet.
     */
    public int $ownerId {
        get => (int) ($this->owner->id ?? 0);
    }

    /**
     * Retrieves the value(s) of a field, or every field, for the owner.
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

        $fields = Cache::get("$this->ownerId", $this->fieldsForeignTable, function () {
            $rows = Db::select(
                $this->fieldsForeignTable,
                [
                    'key',
                    'value'
                ],
                [
                    $this->fieldsForeignKey => $this->ownerId
                ]
            );

            if (! is_array($rows)) {
                return null;
            }

            $grouped = [];
            foreach ($rows as ['key' => $rowKey, 'value' => $value]) {
                $grouped[$rowKey][] = $value;
            }

            return $grouped;
        });

        if ($key === '') {
            return $fields;
        }

        return $isSingle ? ($fields[$key][0] ?? null) : ($fields[$key] ?? null);
    }

    /**
     * The owner's field set exactly as find() would cache it, without querying — null means
     * find() hasn't warmed the cache yet, not that the owner has no fields. protected, not
     * private: {@see TypedField} extends this class and reuses it unchanged.
     *
     * @return array<string, array<int, mixed>>|null
     */
    protected function cached(): ?array
    {
        return Cache::get("$this->ownerId", $this->fieldsForeignTable);
    }

    /**
     * Adds one or more new field values for the owner. A key that already has
     * a value is skipped when $isUnique (the default) — use update() or
     * mutate() to change an existing value instead.
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

        // Reuses find()'s warm whole-owner cache when present (it's the true current set, safe to
        // read) — otherwise a narrow SELECT scoped to just these keys, never the whole set.
        $existing = [];
        if ($isUnique) {
            $cached   = $this->cached();
            $existing = $cached !== null
                ? array_fill_keys(array_intersect(array_keys($attributes), array_keys($cached)), true)
                : array_flip(
                    Db::select(
                        $this->fieldsForeignTable,
                        'key',
                        [
                            $this->fieldsForeignKey => $this->ownerId,
                            'key'                   => array_keys($attributes),
                        ]
                    ) ?? []
                );
        }

        $rows = [];
        foreach ($attributes as $key => $value) {
            if ($this->isEmpty($value) || ($isUnique && isset($existing[$key]))) {
                continue;
            }

            $rows[] = [
                $this->fieldsForeignKey => $this->ownerId,
                'key'                   => $key,
                'value'                 => $value
            ];

            $existing[$key] = true; // also skip a key repeated within this same batch
        }

        if (! $rows) {
            return false;
        }

        $result = Db::insert($this->fieldsForeignTable, $rows);
        if (! $result) {
            return false;
        }

        Cache::forget("$this->ownerId", $this->fieldsForeignTable);

        return $result->rowCount() > 0;
    }

    /**
     * Updates an existing field value for the owner.
     *
     * @param string $key      The field key to update.
     * @param mixed  $value    The new value.
     * @param mixed  $oldValue The specific existing value to replace, if the key holds more than one.
     * @return bool True if a row was changed.
     */
    public function update(string $key, mixed $value, mixed $oldValue = ''): bool
    {
        if (! $this->ownerId) {
            return false;
        }

        $hasOldValue = $oldValue !== '';
        $data        = ['value' => $hasOldValue ? [$oldValue => $value] : $value];
        $conditions  = ['key' => $key, $this->fieldsForeignKey => $this->ownerId];

        $result = $hasOldValue
            ? Db::replace($this->fieldsForeignTable, $data, $conditions)
            : Db::update($this->fieldsForeignTable, $data, $conditions);

        if (! $result) {
            return false;
        }

        Cache::forget("$this->ownerId", $this->fieldsForeignTable);

        return $result->rowCount() > 0;
    }

    /**
     * Deletes field(s) for the owner.
     *
     * @param string $key   Restrict to this field key.
     * @param mixed  $value Restrict to this specific value.
     * @return bool True if a row was removed.
     */
    public function delete(string $key = '', mixed $value = ''): bool
    {
        if (! $this->ownerId) {
            return false;
        }

        $conditions = [$this->fieldsForeignKey => $this->ownerId];
        if ($key !== '') {
            $conditions['key'] = $key;
        }
        if ($value !== '') {
            $conditions['value'] = $value;
        }

        $result = Db::delete($this->fieldsForeignTable, $conditions);
        if (! $result) {
            return false;
        }

        Cache::forget("$this->ownerId", $this->fieldsForeignTable);

        return $result->rowCount() > 0;
    }

    /**
     * Add, update, or delete a field based on the new value: empty deletes
     * it, an existing value gets replaced, otherwise it's added fresh.
     *
     * @param string $key      The field key.
     * @param mixed  $value    The value to set for the field.
     * @param bool   $isUnique Whether the field key should be unique (default: true).
     * @return bool True on success, false on failure.
     */
    public function mutate(string $key, mixed $value = '', bool $isUnique = true): bool
    {
        if ($this->isEmpty($value)) {
            return $this->delete($key);
        }

        $oldValue = $this->find($key);
        if ($this->isEmpty($oldValue)) {
            return $this->add([$key => $value], $isUnique);
        }

        return $this->update($key, $value, $oldValue);
    }

    /**
     * Bulk-syncs the owner's fields to exactly match $fields: missing keys are deleted, new keys
     * inserted, changed values updated. Default chunk size 1000 measured fastest for a 30,000-key
     * sync — below ~300 too many round trips, above ~2000 payloads slow parsing down.
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
        $keys = array_keys($fields);

        // Narrow, chunked SELECT scoped to just these keys — skipped entirely when find()'s
        // whole-owner cache is already warm (see add()'s $cached).
        $cached   = $this->cached();
        $toInsert = [];
        $toUpdate = [];
        foreach (array_chunk($keys, $chunkSize) as $chunkKeys) {
            $existing = [];

            if ($cached !== null) {
                foreach ($chunkKeys as $key) {
                    if (array_key_exists($key, $cached)) {
                        $existing[$key] = $cached[$key][0]; // first stored value per key, matching add()'s default
                    }
                }
            } else {
                $rows = Db::select(
                    $this->fieldsForeignTable,
                    [
                        'key',
                        'value'
                    ],
                    [
                        $this->fieldsForeignKey => $this->ownerId,
                        'key'                   => $chunkKeys
                    ]
                );

                foreach ($rows ?? [] as ['key' => $key, 'value' => $value]) {
                    $existing[$key] ??= $value; // first stored value per key wins, matching add()'s single-value default
                }
            }

            foreach ($chunkKeys as $key) {
                $value = $fields[$key];
                if (! array_key_exists($key, $existing)) {
                    $toInsert[] = [
                        $this->fieldsForeignKey => $this->ownerId,
                        'key'                   => $key,
                        'value'                 => $value
                    ];
                } elseif ($value !== $existing[$key]) {
                    $toUpdate[$key] = $value;
                }
            }
        }

        // Single server-side "delete everything not in $fields" — no rows need to come back to
        // PHP. Empty $fields means delete everything (nothing to NOT-IN against).
        $deleteConditions = [$this->fieldsForeignKey => $this->ownerId] + ($keys ? ['key[!]' => $keys] : []);
        $deleteResult     = Db::delete($this->fieldsForeignTable, ['AND' => $deleteConditions]);
        $result['deleted'] = $deleteResult ? $deleteResult->rowCount() : 0;

        foreach (array_chunk($toInsert, $chunkSize) as $chunk) {
            $rows = Db::insert($this->fieldsForeignTable, $chunk);
            $result['inserted'] += $rows ? $rows->rowCount() : 0;
        }

        // One UPDATE...CASE per chunk (no unique key to upsert on). Placeholders use the
        // chunk-local index, not the field key, since a key can contain invalid characters.
        foreach (array_chunk($toUpdate, $chunkSize, true) as $chunk) {
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

            $sql = "UPDATE <$this->fieldsForeignTable> SET value = CASE `key` " . implode(' ', $cases) . ' END'
                . ' WHERE `key` IN (' . implode(', ', $placeholders) . ") AND `$this->fieldsForeignKey` = :owner_id";

            $rows = Db::query($sql, $params);

            $result['updated'] += $rows ? $rows->rowCount() : 0;
        }

        Cache::forget("$this->ownerId", $this->fieldsForeignTable);

        return $result;
    }

    /**
     * Check value is empty.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
}
