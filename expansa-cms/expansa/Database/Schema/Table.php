<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Expansa\Database\Query\Builder;
use Expansa\Database\Schema\Traits\Columns;
use Expansa\Database\Schema\Traits\Commands;

/**
 * This class provides a fluent interface for defining and manipulating database schema tables.
 *
 * @method Column id(string $column = 'id') Add an auto-incrementing primary key column
 * @method Column ulid(string $column = 'ulid') Add a ULID column
 * @method Column tinyInt(string $column, int $precision = 3) Add a TINYINT column
 * @method Column smallInt(string $column, int $precision = 5) Add a SMALLINT column
 * @method Column mediumInt(string $column, int $precision = 8) Add a MEDIUMINT column
 * @method Column int(string $column, int $precision = 10) Add an INT column
 * @method Column bigInt(string $column, int $precision = 20) Add a BIGINT column
 * @method Column decimal(string $column, int $precision = null, int $scale = null) Add a DECIMAL column
 * @method Column float(string $column) Add a FLOAT column
 * @method Column double(string $column) Add a DOUBLE column
 * @method Column bool(string $column) Add a BOOLEAN column
 * @method Column blob(string $column) Add a BLOB column
 * @method Column string(string $column, int $length = null) Add a STRING column
 * @method Column char(string $column, int $length = null) Add a CHAR column
 * @method Column tinyText(string $column) Add a TINYTEXT column
 * @method Column mediumText(string $column) Add a MEDIUMTEXT column
 * @method Column text(string $column) Add a TEXT column
 * @method Column longText(string $column) Add a LONGTEXT column
 * @method Column uuid(string $column) Add a UUID column
 * @method Column json(string $column) Add a JSON column
 * @method Column enum(string $column, array $allowed) Add an ENUM column
 * @method Column date(string $column) Add a DATE column
 * @method Column time(string $column) Add a TIME column
 * @method Column datetime(string $column) Add a DATETIME column
 * @method Column timestamp(string $column) Add a TIMESTAMP column
 * @method void   timestamps() Add created_at and updated_at columns
 * @method Column addColumn(string $type, string $name, array $parameters = []) Add a new column to the table
 *
 * @method static create() Add a create command for the table
 * @method static rename(string $to) Add a rename command for the table
 * @method static drop() Add a drop command for the table
 * @method static dropIfExists() Add a dropIfExists command for the table
 * @method static renameColumn(string $from, string $to, string $type = null) Add a rename column command
 * @method static dropColumn(string|array $columns) Add a drop column command
 * @method static primary(string|array $columns, string $index = null) Add a primary key command
 * @method static index(string|array $columns, string $index = null) Add an index command
 * @method static unique(string|array $columns, string $index = null) Add a unique index command
 * @method static dropPrimary(string|array $index) Drop a primary key
 * @method static dropIndex(string|array $index) Drop an index
 * @method static dropUnique(string|array $index) Drop a unique index
 * @method CommandForeign foreign(string $column) Add a foreign key constraint
 */
class Table
{
    use Columns;
    use Commands;

    public function __construct(
        public string $name,
        public Builder $connection
    ) {} // phpcs:ignore
}
