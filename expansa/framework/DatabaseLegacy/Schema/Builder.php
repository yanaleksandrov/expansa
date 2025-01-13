<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Schema;

use Closure;
use Expansa\DatabaseLegacy\Contracts\Connection;
use Expansa\DatabaseLegacy\Contracts\SchemaBuilder;

abstract class Builder implements SchemaBuilder
{
    public function __construct(
        protected Connection $connection,
        protected ?Grammar $grammar = null,
    )
    {
        $this->grammar = $this->connection->getSchemaGrammar();
    }

    /**
     * Get or set the database connection instance.
     *
     * @param Connection|null $connection
     * @return Connection|static
     */
    public function connection(Connection $connection = null): static|Connection
    {
        if (is_null($connection)) {
            return $this->connection;
        }
        $this->connection = $connection;

        return $this;
    }

    public function createDatabase(string $name): mixed
    {
        return $this->connection->statement(
            $this->grammar->compileCreateDatabase($name, $this->connection)
        );
    }

    public function dropDatabase(string $name): mixed
    {
        return $this->connection->statement(
            $this->grammar->compileDropDatabase($name, $this->connection)
        );
    }

    public function dropDatabaseIfExists(string $name): mixed
    {
        return $this->connection->statement(
            $this->grammar->compileDropDatabaseIfExists($name, $this->connection)
        );
    }

    public function getTables(): array
    {
        return $this->connection->selectFromWriteConnection(
            $this->grammar->compileGetTables(),
        );
    }

    public function hasTable(string $table): bool
    {
        return count($this->connection->selectFromWriteConnection(
            $this->grammar->compileTableExists(),
            [$this->connection->getTablePrefix() . $table]
        )) > 0;
    }

    public function create(string $table, Closure $callback): void
    {
        $this->build(tap($this->createTable($table), function ($table) use ($callback) {
            $table->create();
            $callback($table);
        }));
    }

    public function table(string $table, Closure $callback): void
    {
        $this->build($this->createTable($table, $callback));
    }

    public function rename(string $from, string $to): void
    {
        $this->build(tap($this->createTable($from), fn ($table) => $table->rename($to)));
    }

    public function drop(string $table): void
    {
        $this->build(tap($this->createTable($table), fn($table) => $table->drop()));
    }

    public function dropIfExists(string $table): void
    {
        $this->build(tap($this->createTable($table), fn($table) => $table->dropIfExists()));
    }

    public function dropAllTables()
    {
        return $this->connection->statement(
            $this->grammar->compileDropTables()
        );
    }

    public function getColumns(string $table): array
    {
        return $this->connection->selectFromWriteConnection(
            $this->grammar->compileGetColumns($table),
        );
    }

    public function getColumnType(string $table, string $column): ?string
    {
        $columns = $this->getColumns($table);

        foreach ($columns as $item) {
            if ($item->name === $column) {
                return $item->type;
            }
        }

        return '';
    }

    public function hasColumn(string $table, string $column): bool
    {
        $tableColumns = array_map(fn ($v) => strtolower($v->name), $this->getColumns($table));

        return in_array(strtolower($column), $tableColumns);
    }

    public function hasColumns(string $table, array $columns): bool
    {
        $tableColumns = array_map(fn ($v) => strtolower($v->name), $this->getColumns($table));

        foreach ($columns as $column) {
            if (! in_array(strtolower($column), $tableColumns)) {
                return false;
            }
        }

        return true;
    }

    public function renameColumn(string $table, string $from, string $to): void
    {
        $this->table($table, function ($table) use ($from, $to) {
            $table->renameColumn($from, $to);
        });
    }

    public function dropColumn(string $table, string|array $columns): void
    {
        $this->table($table, function ($table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    public function dropColumns(string $table, array $column): void
    {
        $this->dropColumn($table, $column);
    }

    public function enableForeignKeys(): void
    {
        $this->connection->statement(
            $this->grammar->compileEnableForeignKeys()
        );
    }

    public function disableForeignKeys(): void
    {
        $this->connection->statement(
            $this->grammar->compileDisableForeignKeys()
        );
    }

    abstract protected function createTable(string $table, Closure $callback = null): Table;

    protected function build($table): void
    {
        $table->build($this->connection, $this->grammar);
    }
}
