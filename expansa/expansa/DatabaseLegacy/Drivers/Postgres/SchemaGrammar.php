<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Drivers\Postgres;

use Expansa\DatabaseLegacy\Schema\Grammar;

class SchemaGrammar extends Grammar
{
    /**
     * Possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = ['Collate', 'Primary', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
     *
     * @var string[]
     */
    protected array $serials = ['bigSerial', 'serial', 'smallSerial'];

    public function addModifiers($sql, $table, $column): string
    {
        if (in_array($column->type, $this->serials)) {
            return $sql . $this->modifyPrimary($column);
        }

        return parent::addModifiers($sql, $table, $column);
    }

    public function compileColumnListing(): string
    {
        return 'SELECT column_name FROM information_schema.columns WHERE table_catalog = ? and table_schema = ? and table_name = ?';
    }

    protected function typeBoolean(SchemaColumn $column): string
    {
        return 'boolean' . (($column->array) ? ' array' : '');
    }

    protected function typeSmallInteger(SchemaColumn $column): string
    {
        return "smallint" . (($column->array) ? ' array' : '');
    }

    protected function typeInteger(SchemaColumn $column): string
    {
        return "integer" . (($column->array) ? ' array' : '');
    }

    protected function typeBigInteger(SchemaColumn $column): string
    {
        return "bigint" . (($column->array) ? ' array' : '');
    }

    protected function typeNumeric(SchemaColumn $column): string
    {
        if (! is_null($column->precision) && ! is_null($column->scale)) {
            return sprintf("numeric(%s,%s)", $column->precision, $column->scale) . (($column->array) ? ' array' : '');
            ;
        } elseif (! is_null($column->precision)) {
            return sprintf("numeric(%s)", $column->precision) . (($column->array) ? ' array' : '');
            ;
        }

        return 'numeric' . (($column->array) ? ' array' : '');
        ;
    }

    protected function typeReal(SchemaColumn $column): string
    {
        return "real" . (($column->array) ? ' array' : '');
        ;
    }

    protected function typeDouble(SchemaColumn $column): string
    {
        return "double precision" . (($column->array) ? ' array' : '');
        ;
    }

    protected function typeSmallSerial(SchemaColumn $column): string
    {
        return "smallserial";
    }

    protected function typeSerial(SchemaColumn $column): string
    {
        return "serial";
    }

    protected function typeBigSerial(SchemaColumn $column): string
    {
        return "bigserial";
    }

    protected function typeChar(SchemaColumn $column): string
    {
        if (is_null($column->length) || $column->length < 1) {
            $column->length = 1;
        }

        return sprintf('char(%s)', $column->length) . (($column->array) ? ' array' : '');
        ;
    }

    protected function typeVarchar(SchemaColumn $column): string
    {
        if (is_null($column->length) || $column->length < 1) {
            $column->length = 255;
        }

        return sprintf('varchar(%s)', $column->length) . (($column->array) ? ' array' : '');
        ;
    }

    protected function typeText(SchemaColumn $column): string
    {
        return "text" . (($column->array) ? ' array' : '');
        ;
    }

    protected function typeTimestamp(SchemaColumn $column): string
    {
        $precision = !is_null($column->precision) ? "({$column->precision})" : '';
        $useCurrent = $column->useCurrent ? ' default CURRENT_TIMESTAMP' : '';

        return sprintf('timestamp%s without time zone%s', $precision, $useCurrent);
    }

    protected function typeTimestampTz(SchemaColumn $column): string
    {
        $precision = !is_null($column->precision) ? "({$column->precision})" : '';
        $useCurrent = $column->useCurrent ? ' default CURRENT_TIMESTAMP' : '';

        return sprintf('timestamp%s with time zone%s', $precision, $useCurrent);
    }

    protected function typeDate(SchemaColumn $column): string
    {
        return 'date';
    }

    protected function typeTime(SchemaColumn $column): string
    {
        return 'time' . (is_null($column->precision) ? '' : "($column->precision)") . ' without time zone';
    }

    protected function typeTimeTz(SchemaColumn $column): string
    {
        return 'time' . (is_null($column->precision) ? '' : "($column->precision)") . ' with time zone';
    }

    protected function typeUuid(SchemaColumn $column): string
    {
        return 'uuid' . ($column->array ? ' array' : '');
    }

    protected function modifyPrimary(SchemaColumn $column)
    {
        if ($column->primary) {
            return ' PRIMARY KEY';
        }
    }

    protected function modifyCollate(SchemaColumn $column)
    {
        if (! is_null($column->collation)) {
            return ' COLLATE ' . $this->wrapValue($column->collation);
        }
    }

    protected function modifyNullable(SchemaColumn $column)
    {
        return $column->nullable ? ' NULL' : ' NOT NULL';
    }

    protected function modifyDefault(SchemaColumn $column)
    {
        if (! is_null($column->default)) {
            return ' DEFAULT ' . $this->wrapDefaultValue($column->default);
        }
    }

    protected function modifyVirtualAs(SchemaColumn $column)
    {
        if ($column->virtualAs !== null) {
            return " GENERATED ALWAYS AS ({$column->virtualAs})";
        }
    }

    protected function modifyStoredAs(SchemaColumn $column)
    {
        if ($column->storedAs !== null) {
            return " GENERATED ALWAYS AS ({$column->storedAs}) STORED";
        }
    }
}
