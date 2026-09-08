<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;
use Expansa\Support\Str;

/**
 * Class Fields
 *
 * A dynamic fields model that can be bound to any database table.
 * Used to store and retrieve additional metadata fields for a parent model.
 *
 * Features:
 * - Automatically binds to a metadata table named "{parent_table}_fields".
 * - Allows mass assignment of all fields.
 * - Caches query results by the parent model ID for improved performance.
 *
 * @property Model  $post     The parent model instance this meta belongs to.
 * @property string $table    The metadata table name (automatically set).
 * @property array  $fillable Fields allowed for mass assignment (all fields).
 */
class Field extends Model
{
    /**
     * The parent model instance that this Meta is associated with.
     *
     * @var Model
     */
    protected Model $post;

    /**
     * Name of the foreign key column on the metadata table that references
     * the parent row. All post types share a single "post_id" column
     * (createFieldsTable() is called with an explicit 'posts' override for
     * them); every other model gets its own "{singular table name}_id".
     *
     * @var string
     */
    protected string $column;

    /**
     * Constructor.
     *
     * @param Model $post       The parent model instance for which meta is created.
     * @param array $attributes Initial model attributes.
     */
    public function __construct(Model $post, array $attributes = [])
    {
        // Not parent::__construct(): Model declares no constructor of its own
        // (nor does anything above it), so there's nothing to call — PHP treats
        // that as a hard error ("Cannot call constructor"), not a silent no-op.
        $this->setAttributes($attributes);

        $this->post   = $post;
        $this->table  = "{$post->getTable()}_fields";
        $this->column = $post instanceof Post ? 'post_id' : Str::singularize($post->getTable()) . '_id';
    }

    /**
     * Retrieves the value of a specific field for the associated object.
     *
     * Benchmark when there are 1 million rows: 1 time - 0.00068 sec, 100000 times - 0.31 sec.
     *
     * @param string $key      The key of the field to retrieve. If empty, get all fields of object.
     * @param bool   $isSingle Whether to limit the result to a single value (default: true).
     *
     * @return mixed The field value or null if the object ID is not set.
     */
    public function find(string $key = '', bool $isSingle = true): mixed
    {
        $postId = $this->post->id;
        if (! $postId) {
            return null;
        }

        $fields = Cache::get("$postId", $this->table, function () use ($postId) {
            $items = Db::select($this->table, ['key', 'value'], [$this->column => $postId]);

            if (!is_array($items)) {
                return null;
            }

            $result = [];
            foreach ($items as ['key' => $key, 'value' => $value]) {
                $result[$key][] = $value;
            }

            return $result;
        });

        if ($key === '') {
            return $fields;
        }

        return $isSingle ? ($fields[$key][0] ?? null) : ($fields[$key] ?? null);
    }

    /**
     * Adds a new field value for the associated object.
     *
     * Benchmark when there are 1 million rows: 1 time - 0.00099 sec, 100000 times - 108.15 sec
     *
     * @param array<string, mixed> $attributes Associative array containing key and value pairs.
     * @param bool                 $isUnique   Whether the field key must be unique (default: true).
     *
     * @return bool True if the field was added successfully, false otherwise.
     */
    public function add(array $attributes, bool $isUnique = true): bool
    {
        $postId = $this->post->id;
        if (! $postId) {
            return false;
        }

        // get all the existing keys in the database at once (with isUnique)
        if ($isUnique) {
            $existingKeysGroup = "{$this->table}_existing_keys";

            $existingKeys = Cache::get("$postId", $existingKeysGroup, fn() => array_flip(
                Db::select(
                    $this->table,
                    'key',
                    [
                        $this->column => $postId,
                        'key'     => array_keys($attributes),
                    ]
                ) ?? []
            ));
        }

        $rowsToInsert = [];
        foreach ($attributes as $key => $value) {
            if ($this->isEmpty($value)) {
                continue;
            }

            // check the database against the ready-made list
            if ($isUnique) {
                if (isset($existingKeys[$key])) {
                    continue;
                }
                // updating the local cache of existing keys
                $existingKeys[$key] = true;
            }

            $rowsToInsert[] = [
                $this->column => $postId,
                'key'     => $key,
                'value'   => $value,
            ];
        }

        if ($rowsToInsert) {
            $result = Db::insert($this->table, $rowsToInsert);

            if ($result) {
                Cache::forget("$postId", $this->table);

                if ($isUnique) {
                    Cache::set("$postId", $existingKeys, $existingKeysGroup);
                }

                return $result->rowCount() > 0;
            }
        }

        return false;
    }

    /**
     * Updates an existing field value for the associated object.
     *
     * @param string $key     The key of the field to update.
     * @param mixed $value    The new value for the field.
     * @param mixed $oldValue The old value of the field (optional).
     *
     * @return bool True if the field was updated successfully, false otherwise.
     */
    public function update(string $key, mixed $value, mixed $oldValue = ''): bool
    {
        $postId = $this->post->id;
        if (! $postId) {
            return false;
        }

        $data       = [ 'value' => $oldValue ? [ $oldValue => $value ] : $value ];
        $conditions = [ 'key' => $key, $this->column => $postId ];

        $result = $oldValue
            ? Db::replace($this->table, $data, $conditions)
            : Db::update($this->table, $data, $conditions);

        if ($result) {
            Cache::forget("$postId", $this->table);

            return $result->rowCount() > 0;
        }

        return false;
    }

    /**
     * Deletes a field for the associated object.
     *
     * @param string $key   The key of the field to delete.
     * @param mixed  $value The value of the field to delete (optional).
     *
     * @return bool True if the field was deleted successfully, false otherwise.
     */
    public function delete(string $key = '', mixed $value = ''): bool
    {
        $postId = $this->post->id;
        if (! $postId) {
            return false;
        }

        $conditions = [$this->column => $postId];

        if ($key) {
            $conditions['key'] = $key;
        }

        if ($value) {
            $conditions['value'] = $value;
        }

        $result = Db::delete($this->table, $conditions);
        if ($result) {
            Cache::forget("$postId", $this->table);

            return $result->rowCount() > 0;
        }

        return false;
    }

    /**
     * Add, update, or delete a field based on the provided value.
     *
     * If the value is empty, the field will be deleted.
     * If the field already exists, it will be updated.
     * Otherwise, a new field will be added.
     *
     * @param string $key      The field key.
     * @param mixed  $value    The value to set for the field.
     * @param bool   $isUnique Whether the field key should be unique (default: true).
     *
     * @return bool True on success, false on failure.
     */
    public function mutate(string $key, mixed $value = '', bool $isUnique = true): bool
    {
        $postId = $this->post->id;
        if (! $postId) {
            return false;
        }

        if ($this->isEmpty($value)) {
            return $this->delete($key);
        }

        $oldValue = $this->get($key);
        if ($this->isEmpty($oldValue)) {
            return $this->add([$key => $value], $isUnique);
        }

        return $this->update($key, $value, $oldValue);
    }

    /**
     * Bulk insert fields.
     *
     * Benchmark when there are 1 million rows: 100000 rows - 29.25 sec
     * TODO: method need refactor
     *
     * @param array $fields
     * @param int $chunkSize
     * @return array
     */
    public function import(array $fields, int $chunkSize = 10000): array
    {
        $result = [ 'deleted' => 0, 'inserted' => 0, 'updated' => 0 ];

        $postId = $this->post->id;
        if (! $postId) {
            return $result;
        }

        $insertData  = [];
        $updateData  = [];
        $updateQuery = [];

        $existsFields = $this->get();

        foreach ($fields as $key => $value) {
            if (! is_string($key) || $this->isEmpty($value)) {
                continue;
            }

            $insertItem = [
                $this->column => $postId,
                'key'     => $key,
                'value'   => $value,
            ];

            if (! isset($existsFields[ $key ])) {
                $insertData[] = $insertItem;
            } elseif ($value !== $existsFields[ $key ]) {
                $updateData[ $key ] = "WHEN :key_$key THEN :value_$key";

                $updateQuery[":key_$key"]   = $key;
                $updateQuery[":value_$key"] = $value;
            }
        }

        $deleteDate = array_diff(array_keys($existsFields ?? []), array_keys($fields));
        if ($deleteDate) {
            $deleteDateParts = array_chunk($deleteDate, $chunkSize);
            foreach ($deleteDateParts as $deleteDatePart) {
                $results = Db::delete(
                    $this->table,
                    [
                        'AND' => [
                            $this->column => $postId,
                            'key'     => $deleteDatePart,
                        ],
                    ]
                );

                if ($results) {
                    $result['inserted'] += $results->rowCount();
                }
            }
        }

        if ($insertData) {
            $insertDataParts = array_chunk($insertData, $chunkSize);
            foreach ($insertDataParts as $insertDataPart) {
                $results = Db::insert($this->table, $insertDataPart);
                if ($results) {
                    $result['inserted'] += $results->rowCount();
                }
            }
        }

        if ($updateData) {
            $updateDataParts  = array_chunk($updateData, $chunkSize, true);
            $updateQueryParts = array_chunk($updateQuery, $chunkSize * 2, true);
            foreach ($updateDataParts as $i => $updateDataPart) {
                $whenPart = implode("\n\t", $updateDataPart);
                $inPart   = implode(', ', array_map(fn($k) => ":key_$k", array_keys($updateDataPart)));

                $sql = "
				UPDATE <$this->table> 
					SET value = CASE `key`
						{$whenPart}
					END
					WHERE `key` IN ($inPart)
				";

                $result['updated'] += Db::query($sql, $updateQueryParts[ $i ])->rowCount();
            }
        }

        Cache::forget("$postId", $this->table);

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
