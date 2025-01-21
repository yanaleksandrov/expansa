<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Schema\Table;
use Expansa\Patterns\Fluent;

trait Indexes
{
    protected function compileIndexes(Table $table, Fluent $command): string
    {
        $index = match ($command->name) {
            'primary'  => 'PRIMARY KEY',
            'index'    => 'INDEX',
            'unique'   => 'UNIQUE',
            'fulltext' => 'FULLTEXT INDEX',
            'spatial'  => 'SPATIAL INDEX',
            default    => '',
        };

        if (!$index) {
            return '';
        }

        $existsColumns  = array_map(fn($column) => $column->getAttributes(), $table->columns);
        $existsLength   = array_column($existsColumns, 'length', 'name');
        $existsColumns  = array_column($existsColumns, 'type', 'name');
        $indexedColumns = (array) ($command->getAttributes()['columns'] ?? []);

        $columns = array_intersect_key($existsColumns, array_flip($indexedColumns));

        $keys = [];

        $isHasIndexLength = false;
        foreach ($columns as $column => $type) {
            $isTextColumnType = preg_match('/^(char|varchar|text|tinytext|mediumtext|longtext)/', $type);
            $textColumnLength = intval($existsLength[$column] ?? 0);

            if ($isTextColumnType) {
                if (
                    (
                        ($index === 'UNIQUE' && ! $isHasIndexLength)
                        ||
                        ($index !== 'UNIQUE')
                    )
                    &&
                    $textColumnLength > EX_DB_MAX_INDEX_LENGTH
                ) {
                    $keys[] = sprintf("`$column`(%d)", EX_DB_MAX_INDEX_LENGTH);
                    if ($index === 'UNIQUE') {
                        $isHasIndexLength = true;
                    }
                } else {
                    $keys[] = "`$column`";
                }
            } else {
                $keys[] = "`$column`";
            }
        }

        return sprintf("ALTER TABLE <$table->name> ADD $index <$command->index> (%s)", implode(', ', $keys));
    }
}
