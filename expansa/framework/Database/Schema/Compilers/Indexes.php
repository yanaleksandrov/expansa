<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Schema\Table;
use Expansa\Patterns\Fluent;

trait Indexes
{
    protected function compileIndexes(Table $table, Fluent $command, string $name): string
    {
        return match ($command->name) {
            'primary'  => $this->compileIndexBase($table, $command, $name, 'PRIMARY KEY'),
            'index'    => $this->compileIndexBase($table, $command, $name, 'INDEX'),
            'unique'   => $this->compileIndexBase($table, $command, $name, 'UNIQUE'),
            'fulltext' => $this->compileIndexBase($table, $command, $name, 'FULLTEXT INDEX'),
            'spatial'  => $this->compileIndexBase($table, $command, $name, 'SPATIAL INDEX'),
            default    => '',
        };
    }

    private function compileIndexBase(Table $table, Fluent $command, string $name, string $type): string
    {
        $result = array_column(
            array_map(fn($column) => $column->getAttributes(), $table->columns),
            'type',
            'name'
        );

        $keys    = [];
        $columns = (array) ($command->getAttributes()['columns'] ?? []);
        foreach ($columns as $column) {
            if (!isset($result[$column])) {
                continue;
            }

            $columnType = $result[$column];
            if (preg_match('/^(char|varchar|text|tinytext|mediumtext|longtext)/', $columnType)) {
                $keys[] = sprintf("`$column`(%d)", EX_DB_MAX_INDEX_LENGTH);
            } else {
                $keys[] = "`$column`";
            }
        }

        return sprintf("ALTER TABLE <$name> ADD $type <$command->index> (%s)", implode(', ', $keys));
    }
}
