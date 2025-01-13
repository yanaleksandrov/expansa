<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Closure;
use Expansa\Database\Abstracts\Base;
use Expansa\Database\Query\Builder as QueryBuilder;
use Expansa\Database\Schema\Compilers\Columns;
use Expansa\Database\Schema\Compilers\Indexes;
use Expansa\Database\Schema\Compilers\Triggers;

class Builder extends Base
{
    use Columns;
    use Indexes;
    use Triggers;

    public function __construct(
        public QueryBuilder $connection,
    ) {} // phpcs:ignore

    public function create(string $name, Closure $callback): void
    {
        $callback($table = new Table($name, $this->connection));

        $columns = [];
        foreach ($table->columns as $column) {
            $columns[$column->name] = array_filter(
                [
                    $this->compileType($column),
                    $this->compileUnsigned($column),
                    $this->compileNullable($column),
                    $this->compileDefaultValue($column),
                    $this->compileAutoIncrement($column),
                    $this->compilePrimaryKey($column),
                ]
            );
        }

        $this->connection->create($name, $columns, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => $this->compileCharset(),
            'COLLATE' => $this->compileCollate(),
        ]);

        foreach ($table->commands as $command) {
            $statement = $this->compileIndexes($table, $command, $name);

            if ($statement) {
                $this->connection->query($statement);
            }
        }
    }

    public function drop(string $name): void
    {
        $this->connection->drop($name);
    }

    public function rename(string $name, string $to): void
    {
        $this->connection->rename($name, $to);
    }
}
