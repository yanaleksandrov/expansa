<?php

declare(strict_types=1);

namespace Expansa\Database;

use Closure;
use Expansa\Facades\Db;
use Expansa\Patterns\Facade;

/**
 * Schema facade for database operations.
 *
 * @method static void create(string $table, Closure $callback)
 * @method static void drop(string $table)
 * @method static void rename(string $from, string $to)
 */
class Schema extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Database\Schema\Builder';
    }

    /**
     * Reuses the Db facade's own resolved connection instead of separately reading the
     * application's config and opening a second one - the framework has no config of its own
     * to read, only what's already been given to Db.
     */
    protected static function getConstructorArgs(): array
    {
        return [Db::instance()];
    }
}
