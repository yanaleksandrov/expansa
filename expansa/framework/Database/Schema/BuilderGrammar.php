<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Expansa\Database\Abstracts\Base;

class BuilderGrammar extends Base
{
    protected function charset(): string
    {
        return EX_DB_CHARSET ? sprintf(' CHARSET=%s', EX_DB_CHARSET) : '';
    }

    protected function collate(): string
    {
        return EX_DB_COLLATION ? sprintf(' COLLATE=%s', EX_DB_COLLATION) : '';
    }

    protected function sql(Table $table): string
    {
        $sql = [];
        foreach ($table->getColumns() as $column) {
            $unsigned  = $this->compileUnsigned($column);
            $type      = $this->compileType($column);
            $primary   = $this->compilePrimary($column);
            $increment = $this->compileIncrement($column);
            $default   = $this->compileDefaultValue($column);
            $nullable  = $this->compileNullable($column);

            $sql[] = PHP_EOL . "`$column->name` {$type}{$unsigned}{$nullable}{$default}{$increment}{$primary}";
        }

        (new Command())->compile($table);

        return implode(', ', $sql) . PHP_EOL;
    }

    protected function compileUnsigned(Column $column): string
    {
        if ($column->unsigned) {
            return ' UNSIGNED';
        }
        return '';
    }

    protected function compilePrimary(Column $column): string
    {
        if ($column->primary) {
            return ' PRIMARY KEY';
        }
        return '';
    }

    protected function compileIncrement(Column $column): string
    {
        if ($column->autoIncrement) {
            return ' AUTO_INCREMENT PRIMARY KEY';
        }
        return '';
    }

    protected function compileType(Column $column): string
    {
        if (isset($column->type)) {
            return match ($column->type) {
                'tinyint',
                'smallint',
                'mediumint',
                'int',
                'bigint',
                'binary',
                'varbinary',
                'char',
                'varchar'    => sprintf('%s(%d)', $column->type, $column->precision ?? $column->length),
                'float',
                'decimal',
                'double'     => sprintf(
                    "$column->type($column->precision%s)",
                    isset($column->scale) ? ",$column->scale" : ''
                ),
                'enum',
                'set'        => sprintf(
                    '%s(%s)',
                    $column->type,
                    implode(', ', array_map(fn($item) => "'$item'", (array) $column->allowed))
                ),
                default      => $column->type,
            };
        }
        return $column->type;
    }

    protected function compileDefaultValue(Column $column): string
    {
        //print_r($column);
        if (! is_null($column->useCurrent)) {
            return match ($column->type) {
                'datetime',
                'timestamp' => ' DEFAULT CURRENT_TIMESTAMP',
                'date'      => ' DEFAULT CURRENT_DATE',
                'time'      => ' DEFAULT CURRENT_TIME',
                default     => '',
            };
        }

        if (! is_null($column->default)) {
            return ' DEFAULT ' . $this->getDefaultValue($column->default);
        }

        return '';
    }

    protected function compileNullable(Column $column): string
    {
        if (! empty($column->virtualAs) || ! empty($column->virtualAsJson)) {
            return '';
        }

        if (! empty($column->storedAs) || ! empty($column->storedAsJson)) {
            return '';
        }

        if ($column->nullable || $column->autoIncrement) {
            return '';
        }

        return ' NOT NULL';
    }

    protected function getDefaultValue(mixed $value = null): string
    {
        if (is_null($value)) {
            return 'NULL';
        }
        return sprintf("'%s'", is_bool($value) ? (int) $value : (string) $value);
    }
}
