<?php

declare(strict_types=1);

namespace app;

use Expansa\Cache;
use Expansa\Database\Db;
use function Expansa\gettype;

/**
 * Represents a field associated with an object, allowing for retrieval,
 * addition, updating, and deletion of field values in a database.
 */
final class Meta
{
    /**
     * DB table name.
     *
     * @var string
     */
    public static string $tableName = 'meta';

    /**
     * The ID of the associated object.
     *
     * @var null|int
     */
    public ?int $entityId;

    /**
     * The name of the database table for the object.
     *
     * @var null|string
     */
    public ?string $entityTable;

    /**
     * DB column name.
     *
     * @var string
     */
    public string $column;

    /**
     * Cache key prefix.
     *
     * @var null|string
     */
    public ?string $cacheGroup;

    /**
     * Initializes the object ID and the table name based on the provided object.
     *
     * @param mixed $object The object associated with the field (e.g., User instance).
     */
    public function __construct(mixed $object)
    {
        [
            $this->entityId,
            $this->entityTable,
            $this->cacheGroup,
        ] = match (true) {
            $object instanceof User => [
                $object->id,
                $object::$table,
                sprintf('user-fields-%d', $object->id),
            ],
            default => [ null, null, null ],
        };
    }

    /**
     * Retrieves the value of a specific field for the associated object.
     *
     * Benchmark when there are 1 million rows: 1 time - 0.00068 sec, 100000 times - 0.05 sec
     *
     * @param string  $type     Database type.
     * @param string  $key      The key of the field to retrieve. If empty, get all fields of object.
     * @param bool    $isSingle Whether to limit the result to a single value (default: true).
     * @return mixed            The field value or null if the object ID is not set.
     */
    public function get(string $type = '', mixed $key = '', bool $isSingle = true): mixed
    {
        return Db::select('table1 AS t1', [
            '[>]table2 AS t2' => ['t1.id' => 't2.table1_id'],
        ], [
            't1.column1',
            't2.column2',
        ]);

        if (! $this->entityId || ! in_array($type, [ 'boolean', 'date', 'datetime', 'float', 'integer', 'text', 'time' ], true)) {
            return null;
        }

        return Cache::get($key, $this->cacheGroup, function () use ($type, $key, $isSingle) {
            $table      = sprintf('%s_%s', self::$tableName, $type);
            $conditions = [ 'entity_id' => $this->entityId, 'entity_table' => $this->entityTable ];

            if ($key) {
                $conditions['key'] = $key;
            }

            $fields = $isSingle && $key
                ? Db::get($table, [ 'key', 'value' ], $conditions)
                : Db::select($table, [ 'key', 'value' ], $conditions);

            $result = null;
            if (is_array($fields)) {
                $result = [];
                if ($isSingle && $key) {
                    return $fields['value'] ?? '';
                }

                foreach ($fields as $field) {
                    $result[ $field['key'] ][] = $field['value'];
                }
            }

            return $result;
        });
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

        $table = $this->getTableName($value);
        if ($isUnique) {
            $count = Db::count($table, [ 'entity_id' => $this->entityId, 'entity_table' => $this->entityTable, 'key' => $key ]);
            if ($count > 0) {
                return false;
            }
        }

        $isInsert = Db::insert(
            $table,
            [
                'entity_id'    => $this->entityId,
                'entity_table' => $this->entityTable,
                'key'          => $key,
                'value'        => $value,
            ]
        )->rowCount() > 0;

        if ($isInsert) {
            Cache::add($key, $value, $this->cacheGroup);
        }
        return $isInsert;
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
                var_dump($i);
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
    private function getTableName(mixed $value): string
    {
        return self::$tableName . '_' . match (gettype($value)) {
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

    /**
     * Create new table into database.
     */
    public static function migrate(): void
    {
        $tableName      = (new Db\Handler())->getTableName(self::$tableName);
        $charsetCollate = (new Db\Handler())->getCharsetCollate();

        // TODO: test benchmarks with different EAV systems
        Db::query(
            "			
			CREATE TABLE IF NOT EXISTS {$tableName}_boolean (
				`id`           BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			    `entity_id`    BIGINT(20)   UNSIGNED NOT NULL DEFAULT '0',
				`entity_table` VARCHAR(255) NOT NULL,
				`key`          VARCHAR(255) DEFAULT NULL,
				`value`        TINYINT(1) DEFAULT NULL,

				PRIMARY KEY (id),

				INDEX `idx_search_by_key` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX `idx_search_by_key_value` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . "), `value`)
			) ENGINE=InnoDB {$charsetCollate};

			CREATE TABLE IF NOT EXISTS {$tableName}_date (
				`id`           BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			    `entity_id`    BIGINT(20)   UNSIGNED NOT NULL DEFAULT '0',
				`entity_table` VARCHAR(255) NOT NULL,
				`key`          VARCHAR(255) DEFAULT NULL,
				`value`        DATE DEFAULT NULL,

				PRIMARY KEY (id),

				INDEX `idx_search_by_key` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX `idx_search_by_key_value` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . "), `value`)
			) ENGINE=InnoDB {$charsetCollate};

			CREATE TABLE IF NOT EXISTS {$tableName}_datetime (
				`id`           BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			    `entity_id`    BIGINT(20)   UNSIGNED NOT NULL DEFAULT '0',
				`entity_table` VARCHAR(255) NOT NULL,
				`key`          VARCHAR(255) DEFAULT NULL,
				`value`        DATETIME DEFAULT NULL,

				PRIMARY KEY (id),

				INDEX `idx_search_by_key` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX `idx_search_by_key_value` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . "), `value`)
			) ENGINE=InnoDB {$charsetCollate};

			CREATE TABLE IF NOT EXISTS {$tableName}_number (
				`id`           BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			    `entity_id`    BIGINT(20)   UNSIGNED NOT NULL DEFAULT '0',
				`entity_table` VARCHAR(255) NOT NULL,
				`key`          VARCHAR(255) DEFAULT NULL,
				`value`        DECIMAL(27, 7) DEFAULT NULL,

				PRIMARY KEY (id),

				INDEX `idx_search_by_key` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX `idx_search_by_key_value` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . "), `value`)
			) ENGINE=InnoDB {$charsetCollate};

			CREATE TABLE IF NOT EXISTS {$tableName}_text (
				`id`           BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			    `entity_id`    BIGINT(20)   UNSIGNED NOT NULL DEFAULT '0',
				`entity_table` VARCHAR(255) NOT NULL,
				`key`          VARCHAR(255) DEFAULT NULL,
				`value`        LONGTEXT,
				`locale`       VARCHAR(100) NOT NULL DEFAULT '',

				PRIMARY KEY (id),

				INDEX idx_entity (`entity_id`, `entity_table`, `locale`),
				FULLTEXT INDEX idx_key_value (`key`, `value`)
			) ENGINE=InnoDB {$charsetCollate};

			CREATE TABLE IF NOT EXISTS {$tableName}_time (
				`id`           BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			    `entity_id`    BIGINT(20)   UNSIGNED NOT NULL DEFAULT '0',
				`entity_table` VARCHAR(255) NOT NULL,
				`key`          VARCHAR(255) DEFAULT NULL,
				`value`        TIME DEFAULT NULL,

				PRIMARY KEY (id),

				INDEX `idx_search_by_key` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . ")),
				INDEX `idx_search_by_key_value` (`entity_id`, `entity_table`, `key`(" . EX_DB_MAX_INDEX_LENGTH . "), `value`)
			) ENGINE=InnoDB {$charsetCollate};
			"
        )->fetchAll();
    }
}
