<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Schema\Column;

trait Columns
{
    /**
     * @return string
     */
    protected function compileCharset(): string
    {
        return $this->connection->charset;
    }

    /**
     * @return string
     */
    protected function compileCollate(): string
    {
        return $this->connection->collation;
    }

    /**
     * @param Column $column
     * @return string "UNIQUE" or "".
     */
    protected function compileUniqueness(Column $column): string
    {
        return $column->unique ? 'UNIQUE' : '';
    }

    /**
     * @param Column $column
     * @return string "UNSIGNED" or "".
     */
    protected function compileUnsigned(Column $column): string
    {
        return $column->unsigned ? 'UNSIGNED' : '';
    }

    /**
     * @param Column $column
     * @return string "AUTO_INCREMENT" or "".
     */
    protected function compileAutoIncrement(Column $column): string
    {
        return $column->autoIncrement ? 'AUTO_INCREMENT' : '';
    }

    /**
     * @param Column $column
     * @return string "PRIMARY KEY" or "".
     */
    protected function compilePrimaryKey(Column $column): string
    {
        if ($column->autoIncrement || $column->primary) {
            return 'PRIMARY KEY';
        }
        return '';
    }

    /**
     * The column's SQL type, e.g. "varchar(255)", "decimal(10,2)", "enum('a', 'b')".
     *
     * @param Column $column
     * @return string
     */
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

    /**
     * The column's SQL DEFAULT clause, if any - CURRENT_TIMESTAMP/CURRENT_DATE/CURRENT_TIME for
     * useCurrent(), otherwise the literal default() value.
     *
     * @param Column $column
     * @return string
     */
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

    /**
     * "NOT NULL", or "" if the column is nullable, auto-incrementing, or generated
     * (virtualAs/storedAs - a generated column's nullability follows its expression, not this).
     *
     * @param Column $column
     * @return string
     */
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

    /**
     * Quotes $value for use as a literal DEFAULT - 'NULL' (bare) for a real null, else a
     * single-quoted string (booleans as 0/1).
     *
     * @param mixed $value
     * @return string
     */
    protected function getDefaultValue(mixed $value = null): string
    {
        if (is_null($value)) {
            return 'NULL';
        }
        return sprintf("'%s'", is_bool($value) ? (int) $value : (string) $value);
    }
}
