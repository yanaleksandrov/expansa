<?php

declare(strict_types=1);

namespace Expansa\Database;

use Expansa\Database\Contracts\Connection;
use Expansa\Database\Contracts\QueryBuilder;
use Expansa\Facades\Facade;
use Iterator;

/**
 * @method static string getName()
 * @method static string getDatabase()
 * @method static string getTablePrefix()
 * @method static QueryBuilder query()
 * @method static QueryBuilder table(string $table, string $as = null)
 * @method static mixed selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static mixed select(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static Iterator cursor(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static int insert(string $query, array $bindings = [])
 * @method static int update(string $query, array $bindings = [])
 * @method static int delete(string $query, array $bindings = [])
 * @method static string|false lastInsertId(string $name = null)
 * @method static mixed statement(string $query, array $bindings = [], bool $useReadPdo = false)
 * @method static bool unprepared(string $query)
 * @method static int affectingStatement(string $query, array $bindings = [])
 *
 * @method static Connection connection(string $name = null)
 * @method static Connection reconnect(string $name = null)
 * @method static void disconnect(string $name = null)
 * @method static void purge(string $name = null)
 * @method static string getDefaultConnection()
 * @method static void setDefaultConnection(string $name)
 *
 * @see \Expansa\Database\Contracts\Connection
 */
class Db extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Database\Manager';
    }
}
