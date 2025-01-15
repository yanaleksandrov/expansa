<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Drivers\Postgres;

use Expansa\DatabaseLegacy\Schema\Builder;

class SchemaBuilder extends Builder
{
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

        return array_map(fn($value) => $value['column_name'], $results);
    }

    public function createTable(string $table, \Closure $callback = null): SchemaTable
    {
        return new SchemaTable($table, $callback);
    }
}
