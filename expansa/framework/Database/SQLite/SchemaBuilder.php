<?php

declare(strict_types=1);

namespace Expansa\Database\SQLite;

use Expansa\Database\Schema\Builder;
use Expansa\Filesystem\Filesystem;

class SchemaBuilder extends Builder
{
    public function createDatabase(string $name): bool
    {
        return (new Filesystem())->put($name, '') !== false;
    }

    public function dropDatabase(string $name): bool
    {
        $files = new Filesystem();

        if (! $files->exists($name)) {
            return false;
        }

        return $files->delete($name);
    }

    public function dropDatabaseIfExists(string $name): bool
    {
        $files = new Filesystem();

        return !$files->exists($name) || $files->delete($name);
    }

    public function getTables(): array
    {
        return array_map(fn ($v) => $v->name, parent::getTables());
    }

    public function dropAllTables(): void
    {
        if ($this->connection->getDatabaseName() === ':memory:') {
            $this->refreshDatabaseFile();
            return;
        }

        $this->connection->select($this->grammar->compileEnableWriteableSchema());
        $this->connection->select($this->grammar->compileDropTables());
        $this->connection->select($this->grammar->compileDisableWriteableSchema());
        $this->connection->select($this->grammar->compileRebuild());
    }

    protected function createTable(string $table, \Closure $callback = null): SchemaTable
    {
        return new SchemaTable($table, $callback, $this->connection->getTablePrefix());
    }

    public function refreshDatabaseFile(): void
    {
        file_put_contents($this->connection->getDatabaseName(), '');
    }
}
