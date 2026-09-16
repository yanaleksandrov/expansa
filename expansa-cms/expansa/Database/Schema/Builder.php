<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Closure;
use Expansa\Database\Query\Builder as QueryBuilder;
use Expansa\Database\Schema\Compilers\Columns;
use Expansa\Database\Schema\Compilers\Indexes;
use Expansa\Database\Schema\Compilers\Triggers;

/**
 * Compiles a Table definition (built by the $callback) into the actual DDL: one CREATE TABLE,
 * then one ALTER TABLE/CREATE TRIGGER statement per index/foreign-key/trigger command collected
 * on the Table while $callback ran.
 */
class Builder
{
    use Columns;
    use Indexes;
    use Triggers;

    public function __construct(

        /**
         * The connection new tables/indexes/triggers are created on.
         */
        public QueryBuilder $connection,
    ) {} // phpcs:ignore

    /**
     * Creates $name with the columns/indexes/foreign keys/triggers $callback declares on the
     * Table it's given - see the class docblock for the two-pass (CREATE, then ALTER/TRIGGER) shape.
     *
     * @param string  $name     Table name.
     * @param Closure $callback Receives the new Table to declare columns/commands on.
     * @return void
     */
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
                    $this->compileUniqueness($column),
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
            $statement = $this->compileIndexes($table, $command);

            if ($statement) {
                $this->connection->query($statement);
            }

            $statement = $this->compileTriggers($table, $command);
            if ($statement) {
                $this->connection->query($statement);
            }
        }
    }

    /**
     * Drops $name.
     *
     * @param string $name Table name.
     * @return void
     */
    public function drop(string $name): void
    {
        $this->connection->drop($name);
    }

    /**
     * Renames $name to $to.
     *
     * @param string $name Current table name.
     * @param string $to   New table name.
     * @return void
     */
    public function rename(string $name, string $to): void
    {
        $this->connection->rename($name, $to);
    }
}
