<?php

declare(strict_types=1);

namespace Expansa;

use PDOStatement;
use Expansa\Database\Exception\InvalidArgumentException;
use Expansa\Database\Manager;
use Expansa\Database\Raw;
use Expansa\Facades\Facade;

/**
 * @method PDOStatement query(string $statement, array $map = [])
 * @method PDOStatement create(string $table, array $columns, array $options = null)
 * @method PDOStatement drop(string $table)
 * @method PDOStatement insert(string $table, array $values, ?string $primaryKey = null)
 * @method PDOStatement update(string $table, array $data, array $where = null)
 * @method PDOStatement delete(string $table, Raw|array $where)
 * @method PDOStatement replace(string $table, array $columns, array $where = null)
 * @method mixed        get(string $table, array $join = null, array|string $columns = null, array $where = null)
 * @method bool         has(string $table, array $join, array $where = null)
 * @method array        rand(string $table, array $join = null, array|string $columns = null, array $where = null)
 * @method null|int     count(string $table, array $join = null, string $column = null, array $where = null)
 * @method null|array   select(string $table, array $join, array|string $columns = null, array $where = null)
 * @method null|string  avg(string $table, array $join, string $column = null, array $where = null)
 * @method null|string  max(string $table, array $join, string $column = null, array $where = null)
 * @method null|string  min(string $table, array $join, string $column = null, array $where = null)
 * @method null|string  sum(string $table, array $join, string $column = null, array $where = null)
 * @method null|string  id(?string $name = null)
 * @method Raw          raw(string $string, array $map = [])
 * @method string       quote(string $string)
 * @method string       version()
 * @method array        schema(string $column = null)
 * @method array        updateSchema()
 */
class Db extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Databaze\Manager';
    }

    protected static function getConstructorArgs(): array
    {
        return [
            [
                'driver'   => EX_DB_DRIVER,
                'database' => EX_DB_NAME,
                'username' => EX_DB_USERNAME,
                'password' => EX_DB_PASSWORD,
                'host'     => EX_DB_HOST,
                'prefix'   => EX_DB_PREFIX,
                'charset'  => EX_DB_CHARSET,
                'testMode' => EX_DB_LOGGING,
                'port'     => EX_DB_PORT,
                'error'    => EX_DB_ERROR_MODE,
            ],
        ];
    }

    /**
     * Create new database connection
     *
     * @param array $options
     * @return null|Manager
     */
    public static function connection(array $options): ?Manager
    {
        try {
            return new Manager($options);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
