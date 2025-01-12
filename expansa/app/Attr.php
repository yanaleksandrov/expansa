<?php

declare(strict_types=1);

namespace app;

use Expansa\Cache;
use Expansa\Db;
use Expansa\Safe;

/**
 * Represents a field associated with an object, allowing for retrieval, addition,
 * updating, and deletion of field values in a database.
 */
final class Attr
{
    /**
     * DB table name.
     *
     * @var string
     */
    public static string $tableName = 'attr';

    /**
     * The ID of the associated object.
     *
     * @var null|int
     */
    public ?int $entityId = null;

    /**
     * DB column name.
     *
     * @var null|string
     */
    public ?string $entityColumn = null;

    /**
     * The name of the database table for the object.
     *
     * @var null|string
     */
    public ?string $table = null;

    /**
     * Cache key prefix.
     *
     * @var null|string
     */
    public ?string $cacheGroup = null;

    private array $types = [ 'text', 'number', 'boolean', 'time', 'date', 'datetime' ];

    /**
     * Initializes the object ID and the table name based on the provided object.
     *
     * @param mixed $object The object associated with the field (e.g., User instance).
     */
    public function __construct(mixed $object)
    {
        if ($object === null) {
            return;
        }

        [
            $this->entityId,
            $this->entityColumn,
            $this->table,
            $this->cacheGroup,
        ] = match (true) {
            $object instanceof User => [
                $object->id,
                'user_id',
                'users_attr',
                sprintf('user-fields-%d', $object->id),
            ],
            default => [ null, null, null, null ],
        };
    }

    /**
     * Adds a new field value for the associated object.
     *
     * Benchmark when there are 1 million rows: 1 time - 0.00029 sec, 100000 times - 26.15 sec
     *
     * @param string $key      The key of the field to add.
     * @param mixed  $value    The value to be added.
     * @param bool   $isUnique Whether the field key must be unique (default: true).
     * @return bool            True if the field was added successfully, false otherwise.
     */
    public function add(string $key, mixed $value, bool $isUnique = true): bool
    {
        if (! $this->entityId) {
            return false;
        }

        if ($isUnique) {
            $count = Db::count($this->table, [ $this->entityColumn => $this->entityId, 'key' => $key ]);
            if ($count > 0) {
                return false;
            }
        }

        $column   = $this->getColumnName($value);
        $isInsert = Db::insert(
            $this->table,
            [
                'key'               => $key,
                $column             => $value,
                $this->entityColumn => $this->entityId,
            ]
        )->rowCount() > 0;

        if ($isInsert) {
            Cache::add($key, $value, $this->cacheGroup);
        }
        return $isInsert;
    }

    /**
     * Deletes a field for the associated object.
     *
     * @param string $key  The key of the field to delete.
     * @param mixed $value The value of the field to delete (optional).
     * @return bool        True if the field was deleted successfully, false otherwise.
     */
    public function delete(string $key = '', mixed $value = ''): bool
    {
        if (! $this->entityId) {
            return false;
        }

        $conditions = [ 'entity_id' => $this->entityId ];

        if ($key) {
            $conditions['key'] = $key;
        }

        if ($value) {
            $conditions['value'] = $value;
        }

        return Db::delete($this->table, $conditions)->rowCount() > 0;
    }

    /**
     * @param string $type
     * @param string $key
     * @return array
     */
    public function fetch(string $type = '', string $key = ''): array
    {
        if (! $this->entityId || ! $this->entityColumn || ( $type && ! in_array($type, $this->types, true) )) {
            return [];
        }

        return Cache::get($key, $this->cacheGroup, function () use ($type, $key) {
            $conditions = [ $this->entityColumn => $this->entityId ];

            if ($key) {
                $conditions['key'] = $key;
            }

            if ($type) {
                $conditions["value_{$type}[!]"] = null;
            }

            return $this->getFormattedSelect(
                Db::select($this->table, [ 'key', ...array_map(fn ($v) => 'value_' . $v, $this->types) ], $conditions)
            );
        });
    }

    /**
     * Retrieves the value of a specific field for the associated object.
     *
     * Benchmark when there are 1 million rows: 1 time - 0.00068 sec, 100000 times - 0.05 sec
     *
     * @param string  $type     Data type, DB column suffix.
     * @param string  $key      The key of the field to retrieve. If empty, get all fields of object.
     * @param bool    $isSingle Whether to limit the result to a single value (default: true).
     * @return mixed            The field value or null if the object ID is not set.
     */
    public function get(string $type, string $key = '', bool $isSingle = true): mixed
    {
        if (! $this->entityId || ! $this->entityColumn || ! in_array($type, $this->types, true)) {
            return null;
        }

        return Cache::get($key, $this->cacheGroup, function () use ($type, $key, $isSingle) {
            $conditions = [ $this->entityColumn => $this->entityId, "value_{$type}[!]" => null ];

            if ($key) {
                $conditions['key'] = $key;
            }

            if ($key && $isSingle) {
                $column = sprintf('value_%s', $type);
                $items  = Db::get($this->table, [ $this->entityColumn, 'key', $column ], $conditions);

                return ! empty($items[ $column ]) ? $this->getFormattedNumber($items[ $column ]) : null;
            }

            return $this->getFormattedSelect(
                Db::select($this->table, [ 'key', "value_{$type}" ], $conditions)
            );
        });
    }

    /**
     * Updates an existing field value for the associated object.
     *
     * @param string $key     The key of the field to update.
     * @param mixed $value    The new value for the field.
     * @param mixed $oldValue The old value of the field (optional).
     * @return bool           True if the field was updated successfully, false otherwise.
     */
    public function update(string $key, mixed $value, mixed $oldValue = ''): bool
    {
        if (! $this->entityId) {
            return false;
        }

        $data       = [ 'value' => $oldValue ? [ $oldValue => $value ] : $value ];
        $conditions = [ 'key' => $key, 'entity_id' => $this->entityId ];

        $query = $oldValue
            ? Db::replace($this->table, $data, $conditions)
            : Db::update($this->table, $data, $conditions);

        $isSuccessful = $query->rowCount() > 0;

        if ($isSuccessful) {
            Cache::add($key, $value, $this->cacheGroup);
        }

        return $isSuccessful;
    }

    /**
     * Add, update or delete field.
     *
     * @param string $key
     * @param mixed|string $value
     * @param bool $isUnique
     * @return bool
     */
    public function mutate(string $key = '', mixed $value = '', bool $isUnique = true): bool
    {
        if (empty($value)) {
            return $this->delete($key);
        }

        $oldValue = $this->get($key);
        if (! empty($oldValue)) {
            return $this->update($key, $value, $oldValue);
        }

        return $this->add($key, $value, $isUnique);
    }

    /**
     * Bulk insert fields.
     *
     * Benchmark when there are 1 million rows (insert, update and delete in the ratio of each action 33%): 100000 rows - 29.25 sec
     * TODO: method need refactor
     *
     * @param array $fields
     * @param int $chunkSize
     * @return array
     */
    public function import(array $fields, int $chunkSize = 10000): array
    {
        $result = [ 'deleted' => 0, 'inserted' => 0, 'updated' => 0 ];

        $insertData  = [];
        $updateData  = [];
        $updateQuery = [];

        $existsFields = self::get();

        foreach ($fields as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $insertItem = [
                'entity_id' => $this->entityId,
                'key'       => $key,
                'value'     => $value,
            ];

            if (! isset($existsFields[ $key ])) {
                $insertData[] = $insertItem;
            } elseif ($value !== $existsFields[ $key ]) {
                $updateData[ $key ] = "WHEN :key_$key THEN :value_$key";

                $updateQuery[":key_{$key}"]   = $key;
                $updateQuery[":value_{$key}"] = $value;
            }
        }

        $deleteDate = array_diff(array_keys($existsFields), array_keys($fields));
        if ($deleteDate) {
            $deleteDateParts = array_chunk($deleteDate, $chunkSize, false);
            foreach ($deleteDateParts as $deleteDatePart) {
                $result['deleted'] += Db::delete(
                    $this->table,
                    [
                        'AND' => [
                            'entity_id' => $this->entityId,
                            'key'       => $deleteDatePart,
                        ],
                    ]
                )->rowCount();
            }
        }

        if ($insertData) {
            $insertDataParts = array_chunk($insertData, $chunkSize, false);
            foreach ($insertDataParts as $i => $insertDataPart) {
                $result['inserted'] += Db::insert($this->table, $insertDataPart)->rowCount();
            }
        }

        if ($updateData) {
            $updateDataParts  = array_chunk($updateData, $chunkSize, true);
            $updateQueryParts = array_chunk($updateQuery, $chunkSize * 2, true);
            foreach ($updateDataParts as $i => $updateDataPart) {
                $whenPart = implode("\n\t", $updateDataPart);
                $inPart   = implode(', ', array_map(fn($k) => ":key_$k", array_keys($updateDataPart)));

                $sql = "
				UPDATE <{$this->table}> 
					SET value = CASE `key`
						{$whenPart}
					END
					WHERE `key` IN ({$inPart})
				";

                $result['updated'] += Db::query($sql, $updateQueryParts[ $i ])->rowCount();
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function getColumnName(mixed $value): string
    {
        return 'value_' . match (gettype($value)) {
            'double',
            'float',
            'integer' => 'number',
            'boolean' => 'boolean',
            'object'  => $this->getObjectType($value),
            'string'  => $this->getStringType($value),
            default   => 'text',
        };
    }

    private function getObjectType(mixed $value): string
    {
        if ($value instanceof \DateTime) {
            $time = $value->format('H:i:s');

            if ($time === '00:00:00') {
                return $value->format('Y-m-d') === '0000-00-00' ? 'time' : 'date';
            }

            return 'datetime';
        }
        return 'text';
    }

    private function getStringType(string $value): string
    {
        // check for date format (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return 'date';
        }
        // check for time format (HH:MM:SS)
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return 'time';
        }
        // check for datetime format (YYYY-MM-DD HH:MM:SS)
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return 'datetime';
        }
        return 'text';
    }

    private function getFormattedNumber(string $value): string
    {
        return rtrim(rtrim($value, '0'), '.');
    }

    private function getFormattedSelect(mixed $items): array
    {
        $result = [];
        if (! is_array($items)) {
            return $result;
        }

        foreach ($items as $item) {
            $key = $item['key'];

            unset($item['key']);

            $values = array_filter($item, fn($value) => $value === 0 || $value);
            $values = array_map(function ($k, $v) {
                if ($k === 'value_number') {
                    return $this->getFormattedNumber($v);
                }
                return $v;
            }, array_keys($values), array_values($values));

            $result[ $key ] = array_merge($result[ $key ] ?? [], $values);
        }

        return $result;
    }

    /**
     * Create new table into database.
     *
     * @param string $table
     * @param string $name
     */
    public static function migrate(string $table, string $name): void
    {
        $tableName      = (new Db\Handler())->getTableName($table . '_' . self::$tableName);
        $charsetCollate = (new Db\Handler())->getCharsetCollate();

        $name  = Safe::tablename($name);
        $table = Safe::tablename($table);

        Db::query(
            "
			CREATE TABLE IF NOT EXISTS {$tableName} (
				`id`         BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
				`{$name}_id` BIGINT(20)   UNSIGNED NOT NULL DEFAULT '0',
				`key`        VARCHAR(255) DEFAULT NULL,

				`value_text`     TEXT,
			    `value_number`   DECIMAL(27, 7) DEFAULT NULL,
				`value_boolean`  TINYINT(1) DEFAULT NULL,
				`value_time`     TIME DEFAULT NULL,
				`value_date`     DATE DEFAULT NULL,
				`value_datetime` DATETIME DEFAULT NULL,

				PRIMARY KEY (id),

				INDEX idx_{$name}_id ({$name}_id, `key`),
				FULLTEXT
			    INDEX idx_key_value_text (`value_text`, `key`),
				INDEX idx_id_key_value_number ({$name}_id, `value_number`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX idx_id_key_value_bool ({$name}_id, `value_boolean`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX idx_id_key_value_time ({$name}_id, `value_time`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX idx_id_key_value_date ({$name}_id, `value_date`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX idx_id_key_value_datetime ({$name}_id, `value_datetime`, `key`(" . EX_DB_MAX_INDEX_LENGTH . "))
			) ENGINE=InnoDB {$charsetCollate};

			CREATE TRIGGER {$tableName}_cascade_delete
				AFTER DELETE ON {$tableName}
				FOR EACH ROW
					BEGIN
						DELETE FROM {$tableName} WHERE {$name}_id = OLD.ID;
					END;"
        )->fetchAll();
    }
}
