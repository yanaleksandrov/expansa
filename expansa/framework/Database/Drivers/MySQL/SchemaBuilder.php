<?php

declare(strict_types=1);

namespace Expansa\Database\Drivers\MySQL;

use Expansa\Database\Schema\Builder;

class SchemaBuilder extends Builder
{
    public function getTables(): array
    {
        return array_map(fn($v) => array_values((array) $v)[0], parent::getTables());
    }

    public function hasTable(string $table): bool
    {
        return count($this->connection->selectFromWriteConnection(
            $this->grammar->compileTableExists(),
            [
                $this->connection->getDatabaseName(),
                $this->connection->getTablePrefix() . $table,
            ]
        )) > 0;
    }

    public function getColumnListing(string $table): array
    {
        $results = $this->connection->select(
            $this->grammar->compileColumnListing(),
            [
                $this->connection->getDatabaseName(),
                $this->connection->getSchema(),
                $this->connection->getTablePrefix() . $table,
            ]
        );

        return array_map(fn ($value) => $value['column_name'], $results);
    }

    public function createTable(string $table, \Closure $callback = null): SchemaTable
    {
        return new SchemaTable($table, $callback);
    }
}
