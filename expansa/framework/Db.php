<?php

declare(strict_types=1);

namespace Expansa;

use PDO;
use PDOException;
use PDOStatement;
use Expansa\Db\Medoo;
use Expansa\Patterns\Singleton;

final class Db
{
    use Singleton;

    /**
     * Db structure
     *
     * @var array
     */
    public static array $schema = [];

    /**
     * Db queries count
     *
     * @var int
     */
    public static int $queries = 0;

    /**
     * Db queries count
     *
     * @var null|Db\Medoo
     */
    public static ?Db\Medoo $connection = null;

    /**
     * Init database connection
     * TODO: добавить возможность поддержки подключения больше одной базы данных, но с учётом кеширования
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        try {
            if (empty(self::$connection)) {
                $options = array_merge(
                    [
                        'database' => defined('EX_DB_NAME') ? EX_DB_NAME : '',
                        'username' => defined('EX_DB_USERNAME') ? EX_DB_USERNAME : '',
                        'password' => defined('EX_DB_PASSWORD') ? EX_DB_PASSWORD : '',
                        'host'     => defined('EX_DB_HOST') ? EX_DB_HOST : 'localhost',
                        'prefix'   => defined('EX_DB_PREFIX') ? EX_DB_PREFIX : 'expansa_',
                        'type'     => defined('EX_DB_DRIVER') ? EX_DB_DRIVER : 'mysql',
                        'charset'  => defined('EX_DB_CHARSET') ? EX_DB_CHARSET : 'utf8mb4',
                    ],
                    $options
                );

                self::$connection = new Medoo($options);
            }
        } catch (PDOException $e) {
            return new Error('database-connection', I18n::_t('There is a problem with connecting to the database'));
        }

        return self::$connection;
    }

    /**
     * Execute customized raw statement.
     *
     * @param string $statement The raw SQL statement.
     * @param array $map The array of input parameters value for prepared statement.
     * @return PDOStatement|null
     */
    public static function query(string $statement, array $map = []): ?PDOStatement
    {
        return self::$connection?->query($statement, $map);
    }

    /**
     * Execute the raw statement.
     *
     * @param string $statement The SQL statement.
     * @param array $map The array of input parameters value for prepared statement.
     * @param callable|null $callback
     * @return PDOStatement|null
     */
    public static function exec(string $statement, array $map = [], callable $callback = null): ?PDOStatement
    {
        return self::$connection?->exec($statement, $map, $callback);
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return PDOStatement|null
     */
    public static function drop(string $table): ?PDOStatement
    {
        return self::$connection?->drop($table);
    }

    /**
     * Select data from the table.
     *
     * @param string $table
     * @param array $join
     * @param array|string $columns
     * @param array $where
     * @return array|null
     */
    public static function select(string $table, $join, $columns = null, $where = null): ?array
    {
        return self::$connection?->select($table, $join, $columns, $where);
    }

    /**
     * Insert one or more records into the table.
     *
     * @param string $table
     * @param array $values
     * @param string|null $primaryKey
     * @return PDOStatement|null
     */
    public static function insert(string $table, array $values, string $primaryKey = null): ?PDOStatement
    {
        return self::$connection?->insert($table, $values, $primaryKey);
    }

    /**
     * Modify data from the table.
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return PDOStatement|null
     */
    public static function update(string $table, $data, $where = null): ?PDOStatement
    {
        return self::$connection?->update($table, $data, $where);
    }

    /**
     * Delete data from the table.
     *
     * @param string $table
     * @param array $where
     * @return PDOStatement|null
     */
    public static function delete(string $table, $where): ?PDOStatement
    {
        return self::$connection?->delete($table, $where);
    }

    /**
     * Replace old data with a new one.
     *
     * @param string $table
     * @param array $columns
     * @param array $where
     * @return PDOStatement|null
     */
    public static function replace(string $table, array $columns, $where = null): ?PDOStatement
    {
        return self::$connection?->replace($table, $columns, $where);
    }

    /**
     * Get only one record from the table.
     *
     * @param string $table
     * @param array $join
     * @param array|string $columns
     * @param array $where
     * @return mixed
     */
    public static function get(string $table, $join = null, $columns = null, $where = null)
    {
        return self::$connection?->get($table, $join, $columns, $where);
    }

    /**
     * Determine whether the target data existed from the table.
     *
     * @param string $table
     * @param array $join
     * @param array $where
     * @return bool
     */
    public static function has(string $table, $join, $where = null): bool
    {
        return self::$connection?->has($table, $join, $where);
    }

    /**
     * Randomly fetch data from the table.
     *
     * @param string $table
     * @param array $join
     * @param array|string $columns
     * @param array $where
     * @return array
     */
    public static function rand(string $table, $join = null, $columns = null, $where = null): array
    {
        return self::$connection?->rand($table, $join, $columns, $where);
    }

    /**
     * Count the number of rows from the table.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return int|null
     */
    public static function count(string $table, $join = null, $column = null, $where = null): ?int
    {
        return self::$connection?->count($table, $join, $column, $where);
    }

    /**
     * Calculate the average value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public static function avg(string $table, $join, $column = null, $where = null): ?string
    {
        return self::$connection?->avg($table, $join, $column, $where);
    }

    /**
     * Get the maximum value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public static function max(string $table, $join, $column = null, $where = null): ?string
    {
        return self::$connection?->max($table, $join, $column, $where);
    }

    /**
     * Get the minimum value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public static function min(string $table, $join, $column = null, $where = null): ?string
    {
        return self::$connection?->min($table, $join, $column, $where);
    }

    /**
     * Calculate the total value of the column.
     *
     * @param string $table
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public static function sum(string $table, $join, $column = null, $where = null): ?string
    {
        return self::$connection?->sum($table, $join, $column, $where);
    }

    /**
     * Enable debug mode and output readable statement string.
     *
     * @codeCoverageIgnore
     * @return Db\Medoo
     */
    public static function debug(): Db\Medoo
    {
        return self::$connection?->debug();
    }

    /**
     * Return the last performed statement.
     *
     * @return string|null
     */
    public static function last(): ?string
    {
        return self::$connection?->last();
    }

    /**
     * Get count of queries
     */
    public static function queries(): int
    {
        return self::$queries;
    }

    /**
     * Get count of queries
     */
    public static function info(): ?array
    {
        if (self::$connection instanceof Db\Medoo) {
            return self::$connection?->info();
        }
        return null;
    }

    /**
     * Return the ID for the last inserted row.
     *
     * @param string|null $name
     * @return string|null
     * @codeCoverageIgnore
     */
    public static function id(string $name = null): ?string
    {
        return self::$connection?->id($name);
    }

    /**
     * Get database version
     */
    public static function version(): array|string|null
    {
        $info = self::info();
        if (isset($info['version'])) {
            $version = preg_replace('/[^0-9.].*/', '', $info['version']);
            return preg_replace('/^\D+([\d.]+).*/', '$1', $version);
        }
        return null;
    }

    /**
     * Get database schema
     *
     * @see   https://stackoverflow.com/questions/52642542/how-to-extract-column-name-and-type-from-mysql
     */
    public static function schema(string $_column = null)
    {
        if (! self::$schema) {
            $query = self::query(
                '
				SELECT
    				COLUMN_NAME as name, DATA_TYPE as type, TABLE_NAME as tbl
				FROM
				    INFORMATION_SCHEMA.COLUMNS
				WHERE
				    TABLE_SCHEMA = :database',
                [
                    ':database' => EX_DB_NAME,
                ]
            );

            if ($query instanceof PDOStatement) {
                $columns = $query->fetchAll(PDO::FETCH_ASSOC);
                if (is_array($columns)) {
                    foreach ($columns as $column) {
                        self::$schema[ $column['tbl'] ][ $column['name'] ] = $column['type'];
                    }
                }
            }
        }

        if (isset(self::$schema[ $_column ])) {
            return self::$schema[ $_column ];
        }
        return self::$schema;
    }

    /**
     * Update database schema.
     */
    public static function updateSchema()
    {
        if (self::$schema) {
            self::$schema = [];
        }
        return self::schema();
    }

    /**
     * Try to check database connection
     *
     * @return Error|Medoo|null
     */
    public static function check(): Error|Medoo|null
    {
        try {
            return self::$connection;
        } catch (PDOException $e) {
            return new Error('database-connection', I18n::_t('There is a problem with connecting to the database'));
        }
    }
}
