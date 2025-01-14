<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Schema\Column;

trait Columns
{
    protected function compileCharset(): string
    {
        return EX_DB_CHARSET ?? '';
    }

    protected function compileCollate(): string
    {
        return EX_DB_COLLATION ?? '';
    }

    protected function compileUniqueness(Column $column): string
    {
        return $column->unique ? 'UNIQUE' : '';
    }

    protected function compileUnsigned(Column $column): string
    {
        return $column->unsigned ? 'UNSIGNED' : '';
    }

    protected function compileAutoIncrement(Column $column): string
    {
        return $column->autoIncrement ? 'AUTO_INCREMENT' : '';
    }

    protected function compilePrimaryKey(Column $column): string
    {
        if ($column->autoIncrement || $column->primary) {
            return 'PRIMARY KEY';
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
                'varchar'    => sprintf('%s(%d)', $column->type, $column->precision ?? $column->length ?? ''),
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
        return mb_strtolower($column->type);
    }

    protected function compileDefaultValue(Column $column): string
    {
        if (! is_null($column->useCurrent)) {
            return match ($column->type) {
                'datetime',
                'timestamp' => 'DEFAULT CURRENT_TIMESTAMP',
                'date'      => 'DEFAULT CURRENT_DATE',
                'time'      => 'DEFAULT CURRENT_TIME',
                default     => '',
            };
        }

        if (! is_null($column->default)) {
            return 'DEFAULT ' . $this->getDefaultValue($column->default);
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

        return 'NOT NULL';
    }

    protected function getDefaultValue(mixed $value = null): string
    {
        if (is_null($value)) {
            return 'NULL';
        }
        return sprintf("'%s'", is_bool($value) ? (int) $value : (string) $value);
    }
}
