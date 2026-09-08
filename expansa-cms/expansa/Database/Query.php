<?php

declare(strict_types=1);

namespace Expansa\Database;

use Exception;
use Expansa\Database\Model\HasSoftDeletes;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;

/**
 * Class Query
 *
 * A standalone query builder / ORM-like class that mimics Laravel 12 Eloquent model methods.
 *
 * This class provides a fluent interface for querying and manipulating records of a given Model.
 * It supports common ORM operations such as finding by primary key, filtering with where conditions,
 * aggregations, chunking results, and managing soft deletes.
 *
 * The Query instance is tightly coupled with a specific Model class, passed via constructor,
 * allowing returned results to be hydrated as Model instances with full attribute casting and accessors.
 *
 * Example usage:
 * ```php
 * $user  = (new Query(User::class))->find(1);
 * $users = (new Query(User::class))->get();
 * ```
 *
 * Caching is integrated for find operations to optimize repeated lookups.
 */
class Query
{
    /**
     * Accumulated where conditions, in Medoo's keyed where-clause format.
     *
     * @var array<string, mixed>
     */
    protected array $wheres = [];

    /**
     * Whether soft-deleted records should be included in the results.
     *
     * @var bool
     */
    protected bool $withTrashed = false;

    /**
     * Whether the results should be restricted to soft-deleted records only.
     *
     * @var bool
     */
    protected bool $onlyTrashed = false;

    public function __construct(
        /**
         * The fully qualified class name of the Eloquent model.
         *
         * @var Model
         */
        protected readonly Model $model
    ) {} // phpcs:ignore

    /**
     * Find a record by primary key or another field value.
     *
     * This method attempts to retrieve a model instance matching the given value
     * for the specified field, using caching to optimize repeated lookups.
     *
     * @param int|string $value The value to search for (e.g., primary key).
     * @param string     $by    The field name to search by. Defaults to 'id'.
     *
     * @return Model|null Returns the model instance if found; otherwise, null.
     */
    public function get(int|string $value, string $by = 'id'): ?Model
    {
        return Cache::get("$value", $this->model->getTable(), function () use ($value, $by) {
            $data = Db::get($this->model->getTable(), '*', $this->scopedWheres([$by => $value]));
            if (is_array($data)) {
                return $this->model::newFrom($data);
            }
            return null;
        });
    }

    /**
     * Get all matching records, hydrated as model instances.
     *
     * @return array<int, Model>
     */
    public function find(): array
    {
        $rows = Db::select($this->model->getTable(), '*', $this->scopedWheres($this->wheres)) ?? [];

        return array_map(fn(array $row) => $this->model::newFrom($row), $rows);
    }

    /**
     * Get the first matching record, hydrated as a model instance.
     *
     * @return null|Model
     */
    public function first(): ?Model
    {
        $rows = Db::select($this->model->getTable(), '*', $this->scopedWheres(array_merge($this->wheres, ['LIMIT' => 1]))) ?? [];

        return isset($rows[0]) ? $this->model::newFrom($rows[0]) : null;
    }

    /**
     * Get every record for the model, ignoring any where conditions applied to this instance.
     *
     * @return array<int, Model>
     */
    public function all(): array
    {
        $rows = Db::select($this->model->getTable(), '*', $this->scopedWheres([])) ?? [];

        return array_map(fn(array $row) => $this->model::newFrom($row), $rows);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param array $args
     * @return $this
     */
    public function where(array $args): self
    {
        $this->wheres = array_merge($this->wheres, $args);

        return $this;
    }

    /**
     * Include soft-deleted records in the results.
     *
     * @return $this
     */
    public function withTrashed(): self
    {
        $this->withTrashed = true;

        return $this;
    }

    /**
     * Restrict the results to soft-deleted records only.
     *
     * @return $this
     */
    public function onlyTrashed(): self
    {
        $this->withTrashed = true;
        $this->onlyTrashed = true;

        return $this;
    }

    /**
     * Apply the soft-delete scope (if the model uses it) on top of the given where conditions.
     *
     * @param array $where
     * @return array
     */
    protected function scopedWheres(array $where): array
    {
        if (! in_array(HasSoftDeletes::class, class_uses($this->model), true)) {
            return $where;
        }

        $column = $this->model->getDeletedAtColumn();

        if ($this->onlyTrashed) {
            return array_merge($where, ["{$column}[!]" => null]);
        }

        if ($this->withTrashed) {
            return $where;
        }

        return array_merge($where, [$column => null]);
    }

    /**
     * Chunk results and process in pieces.
     *
     * @param  int      $size
     * @param  callable $callback
     * @return void
     */
    public function chunk(int $size, callable $callback): void
    {
        $page = 0;

        do {
            $results = Db::select($this->model->getTable(), '*', $this->scopedWheres(array_merge($this->wheres, ['LIMIT' => [$page * $size, $size]]))) ?? [];

            if ($results) {
                $callback($results);
            }

            $page++;
        } while (count($results) === $size);
    }

    /**
     * Save the current record: updates it if it already has an ID, otherwise
     * inserts it and back-fills the generated ID onto the model.
     *
     * An update only writes attributes that actually changed since the model
     * was loaded (or last saved); a save with nothing dirty is a no-op.
     *
     * @return null|Model
     */
    public function save(): ?Model
    {
        $id = $this->model->id ?? null;

        if ($id) {
            $changes = $this->model->getChanges();
            if ($changes) {
                Db::update($this->model->getTable(), $changes, ['id' => $id]);
                Cache::forget("$id", $this->model->getTable());
            }

            $this->model->syncOriginals();

            return $this->model;
        }

        if (! Db::insert($this->model->getTable(), $this->model->getAttributes())) {
            return null;
        }

        $id = Db::id();
        if (! $id) {
            return null;
        }

        // Db::id() always returns a string (PDO::lastInsertId()'s own contract),
        // while a freshly-fetched row types its numeric id column as an int —
        // cast here so a just-inserted model's id has the same type as one
        // loaded via get(), and the two compare equal with ===.
        $this->model->setAttribute('id', (int) $id);
        $this->model->syncOriginals();

        return $this->model;
    }

    /**
     * Delete the current record. Models using {@see HasSoftDeletes} are soft-deleted
     * (the "deleted at" column is stamped) instead of being removed from the table.
     *
     * @return int The number of rows affected.
     */
    public function delete(): int
    {
        if (in_array(HasSoftDeletes::class, class_uses($this->model), true)) {
            $results = Db::update(
                $this->model->getTable(),
                [$this->model->getDeletedAtColumn() => date('Y-m-d H:i:s')],
                ['id' => $this->model->id ?? 0]
            );
        } else {
            $results = Db::delete($this->model->getTable(), ['id' => $this->model->id ?? 0]);
        }

        Cache::forget("{$this->model->id}", $this->model->getTable());

        return $results ? $results->rowCount() : 0;
    }

    /**
     * Restore a soft-deleted record (clears the "deleted at" column). No-op for
     * models that don't use {@see HasSoftDeletes}.
     *
     * @return int The number of rows affected.
     */
    public function restore(): int
    {
        if (! in_array(HasSoftDeletes::class, class_uses($this->model), true)) {
            return 0;
        }

        $results = Db::update(
            $this->model->getTable(),
            [$this->model->getDeletedAtColumn() => null],
            ['id' => $this->model->id ?? 0]
        );

        Cache::forget("{$this->model->id}", $this->model->getTable());

        return $results ? $results->rowCount() : 0;
    }

    /**
     * Check if a record exists in the database based on given fields.
     *
     * @param array $data An associative array of field names and values to match.
     * @return bool Returns true if at least one record matches the fields, false otherwise.
     */
    public function exists(array $data): bool
    {
        $results = Db::select($this->model->getTable(), '*', ['OR' => $data]);
        if ($results) {
            return true;
        }
        return false;
    }
}
