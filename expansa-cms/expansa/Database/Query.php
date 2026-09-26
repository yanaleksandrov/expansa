<?php

declare(strict_types=1);

namespace Expansa\Database;

use Exception;
use Expansa\Database\Model\HasSoftDeletes;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;

/**
 * A fluent query builder scoped to one Model instance - finding by primary key or where
 * conditions, aggregations, chunking, soft deletes, and save()/delete()/restore(). Rows come
 * back hydrated via {@see Model::make()}, with full attribute mutators/accessors applied.
 *
 * Application code normally never constructs this directly: {@see Model::__callStatic()} and
 * {@see Model::__call()} build one on demand for any Query method called on the model itself,
 * e.g. `User::get(1)` or `$user->save()`.
 *
 * get() results are cached; the rest of the Query methods query the database directly on every call.
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
         * The model instance this query is scoped to.
         */
        protected readonly Model $model,
    ) {} // phpcs:ignore

    /**
     * Find a record by primary key or another field value. Only an 'id' lookup is cached -
     * caching by any other field would need the field name folded into the cache key (two
     * different fields can share the same value, e.g. a nicename that happens to equal another
     * row's id) and, more importantly, {@see self::save()}/{@see self::delete()}/{@see self::restore()}
     * below only ever invalidate the "id" entry - a row cached under a different field would
     * keep serving stale data after being changed.
     *
     * @param int|string $value The value to search for (e.g., primary key).
     * @param string     $by    The field name to search by. Defaults to 'id'.
     *
     * @return Model|null Returns the model instance if found; otherwise, null.
     */
    public function get(int|string $value, string $by = 'id'): ?Model
    {
        $fetch = function () use ($value, $by) {
            $data = Db::get($this->model->getTable(), '*', $this->scopedWheres([$by => $value]));

            return is_array($data) ? $this->model::make($data) : null;
        };

        return $by === 'id' ? Cache::get("$value", $this->model->getTable(), $fetch) : $fetch();
    }

    /**
     * Get all matching records, hydrated as model instances.
     *
     * @return array<int, Model>
     */
    public function find(): array
    {
        $rows = Db::select($this->model->getTable(), '*', $this->scopedWheres($this->wheres)) ?? [];

        return array_map($this->model::make(...), $rows);
    }

    /**
     * Get the first matching record, hydrated as a model instance.
     *
     * @return null|Model
     */
    public function first(): ?Model
    {
        $rows = Db::select($this->model->getTable(), '*', $this->scopedWheres(array_merge($this->wheres, ['LIMIT' => 1]))) ?? [];

        return isset($rows[0]) ? $this->model::make($rows[0]) : null;
    }

    /**
     * Get every record for the model, ignoring any where conditions applied to this instance.
     *
     * @return array<int, Model>
     */
    public function all(): array
    {
        $rows = Db::select($this->model->getTable(), '*', $this->scopedWheres([])) ?? [];

        return array_map($this->model::make(...), $rows);
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
        if (! $this->model->usesTrait(HasSoftDeletes::class)) {
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
                if (! Db::update($this->model->getTable(), $changes, ['id' => $id])) {
                    return null;
                }

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
        if ($this->model->usesTrait(HasSoftDeletes::class)) {
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
        if (! $this->model->usesTrait(HasSoftDeletes::class)) {
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
        return (bool) Db::select($this->model->getTable(), 'id', ['OR' => $data, 'LIMIT' => 1]);
    }
}
