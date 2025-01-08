<?php

declare(strict_types=1);

namespace Expansa\Database\SQLite;

use Expansa\Database\Contracts\Connection;
use Expansa\Database\Contracts\DatabaseException;
use Expansa\Database\Schema\Table;

class SchemaTable extends Table
{
    protected function isValidConnection(Connection $connection): void
    {
        if (count($this->getCommandsByNamed(['dropColumn', 'renameColumn'])) > 1) {
            throw new DatabaseException("SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.");
        }

        if (count($this->getCommandsByNamed(['dropForeign'])) > 1) {
            throw new DatabaseException("SQLite doesn't support dropping foreign keys (you would need to re-create the table).");
        }
    }
}
