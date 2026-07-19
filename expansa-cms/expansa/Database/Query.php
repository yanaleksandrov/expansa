<?php

declare(strict_types=1);

namespace Expansa\Database;

use Exception;
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
            $data = Db::get($this->model->getTable(), '*', [$by => $value]);
            if (is_array($data)) {
                return $this->model::newFrom($data);
            }
            return null;
        });
    }

    /**
     * Get all matching records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function find(): array
    {
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param array $args
     * @return $this
     */
    public function where(array $args): self
    {
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
    }

    /**
     * Save the current record (insert or update).
     *
     * @return null|Model
     */
    public function save(): ?Model
    {
        $result = Db::insert($this->model->getTable(), $this->model->getAttributes());
        if ($result) {
            $id = Db::id();
            if ($id) {
                return $this->model;
            }
        }
        return null;
    }

    /**
     * Delete records matching the given value on a specified field.
     *
     * @return int The number of rows deleted.
     */
    public function delete(): int
    {
        $results = Db::delete($this->model->getTable(), ['id' => $this->model->id ?? 0]);
        if ($results) {
            return $results->rowCount();
        }
        return 0;
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
