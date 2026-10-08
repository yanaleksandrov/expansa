<?php

declare(strict_types=1);

namespace Expansa\Database;

use Expansa\Database\Internal\Cache;
use Expansa\Database\Traits\HasSoftDeletes;
use Expansa\Database\Traits\HasUuid;
use Expansa\Facades\Db;
use Expansa\Support\Str;

/**
 * Queries of one model: find by key or where conditions, chunks, soft deletes, save(), delete(), restore().
 * Rows come back as models through Model::hydrate().
 *
 * Usually created by the model itself for a Query method called on it: `User::get(1)`, `$user->save()`.
 * Only get() by id is cached, the other methods always query the database.
 *
 * @package Expansa\Database
 */
final class Query
{
    /**
     * Accumulated where conditions, in Medoo's keyed where-clause format.
     *
     * @var array<string, mixed>
     */
    private array $wheres = [];

    /**
     * Whether soft-deleted records should be included in the results.
     *
     * @var bool
     */
    private bool $withTrashed = false;

    /**
     * Whether the results should be restricted to soft-deleted records only.
     *
     * @var bool
     */
    private bool $onlyTrashed = false;

    public function __construct(

        /**
         * The model instance this query is scoped to.
         */
        private readonly Model $model,
    ) {}

    /**
     * Find a row by primary key or another field. Only the id lookup is cached:
     * save(), delete() and restore() forget only the id entry.
     *
     * @param int|string $value
     * @param string     $by
     * @return Model|null
     */
    public function get(int|string $value, string $by = 'id'): ?Model
    {
        $query = function () use ($value, $by): ?Model {
            $row = Db::get($this->model->table, '*', $this->scopedWheres([$by => $value]));

            return is_array($row) ? $this->model::hydrate($row) : null;
        };

        return $by === 'id' ? Cache::get((string) $value, $this->model->table, $query) : $query();
    }

    /**
     * Get all matching records, hydrated as model instances.
     *
     * @return array<int, Model>
     */
    public function find(): array
    {
        $rows = Db::select($this->model->table, '*', $this->scopedWheres($this->wheres)) ?? [];

        return array_map($this->model::hydrate(...), $rows);
    }

    /**
     * Get the first matching record, hydrated as a model instance.
     *
     * @return null|Model
     */
    public function first(): ?Model
    {
        $rows = Db::select($this->model->table, '*', $this->scopedWheres(array_merge($this->wheres, ['LIMIT' => 1]))) ?? [];

        return isset($rows[0]) ? $this->model::hydrate($rows[0]) : null;
    }

    /**
     * Get every record for the model, ignoring any where conditions applied to this instance.
     *
     * @return array<int, Model>
     */
    public function all(): array
    {
        $rows = Db::select($this->model->table, '*', $this->scopedWheres([])) ?? [];

        return array_map($this->model::hydrate(...), $rows);
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
    private function scopedWheres(array $where): array
    {
        if (! $this->model->usesTrait(HasSoftDeletes::class)) {
            return $where;
        }

        $column = $this->model->deletedAtColumn;

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
            $results = Db::select($this->model->table, '*', $this->scopedWheres(array_merge($this->wheres, ['LIMIT' => [$page * $size, $size]]))) ?? [];

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
                if (! Db::update($this->model->table, $changes, ['id' => $id])) {
                    return null;
                }

                Cache::forget("$id", $this->model->table);
            }

            $this->model->syncOriginals();

            return $this->model;
        }

        if ($this->model->usesTrait(HasUuid::class) && empty($this->model->attributes[$this->model->uuidColumn])) {
            $this->model->setAttribute($this->model->uuidColumn, Str::uuid7());
        }

        if (! Db::insert($this->model->table, $this->model->attributes)) {
            return null;
        }

        $id = Db::id();
        if (! $id) {
            return null;
        }

        // Db::id() is a string, a fetched row has an int id
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
                $this->model->table,
                [$this->model->deletedAtColumn => date('Y-m-d H:i:s')],
                ['id' => $this->model->id ?? 0]
            );
        } else {
            $results = Db::delete($this->model->table, ['id' => $this->model->id ?? 0]);
        }

        Cache::forget("{$this->model->id}", $this->model->table);

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
            $this->model->table,
            [$this->model->deletedAtColumn => null],
            ['id' => $this->model->id ?? 0]
        );

        Cache::forget("{$this->model->id}", $this->model->table);

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
        return (bool) Db::select($this->model->table, 'id', ['OR' => $data, 'LIMIT' => 1]);
    }
}
