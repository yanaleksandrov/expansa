<?php
declare(strict_types=1);

namespace Expansa\Database\Postgres\Schema;

use Expansa\Database\Schema\Builder as BuilderAbstract;

class Builder extends BuilderAbstract
{
    public function getColumnListing(string $table): array
    {
        $results = $this->connection->select(
            $this->grammar->compileColumnListing(), [
                $this->connection->getDatabaseName(),
                $this->connection->getSchema(),
                $this->connection->getTablePrefix().$table
            ]
        );

	    return array_map(fn($value) => $value['column_name'], $results);
    }

    public function createTable(string $table, \Closure $callback = null): Table
    {
        return new Table($table, $callback);
    }
}