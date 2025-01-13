<?php

declare(strict_types=1);

namespace Expansa\Database;

use Expansa\Database\Query\Builder;
use Expansa\Database\Query\Raw;
use Expansa\Database\Exception\InvalidArgumentException;
use Expansa\Facades\Facade;
use PDOStatement;

/**
 * This class provides a facade for database interactions, offering a variety of methods to execute
 * common SQL operations such as querying, creating, dropping tables, and performing CRUD operations.
 * It also includes methods for more advanced operations like schema management and raw SQL execution.
 *
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
 * @method null|array   select(string $table, string|array $join, array|string $columns = null, array $where = null)
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
        return '\Expansa\Database\Query\Builder';
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
     * @return null|Builder
     */
    public static function connection(array $options): ?Builder
    {
        try {
            return new Builder($options);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
