<?php

declare(strict_types=1);

namespace Expansa\Database\Drivers\Postgres;

use Expansa\Database\Contracts\DatabaseException;
use Expansa\Database\Schema\Table as TableBase;

class SchemaTable extends TableBase
{
    protected string $columnClassDefault = SchemaColumn::class;

    /*
    |--------------------------------------------------------------------------
    | Custom types
    |--------------------------------------------------------------------------
    */

    /**
     * Create column as primary auto-incrementing big integer (8-byte, 1 to 9223372036854775807).
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function id(string $column = 'id'): SchemaColumn
    {
        return $this->addColumn('bigSerial', $column, ['primary' => true]);
    }

    /**
     * Create string column
     *
     * @param string   $column
     * @param int|null $length
     * @return SchemaColumn
     */
    public function string(string $column, int $length = null): SchemaColumn
    {
        if (is_null($length)) {
            return $this->text($column);
        }

        return $this->varchar($column, $length);
    }

    public function timestamps(int $precision = 0): void
    {
        $this->timestamp('created_at', $precision);
        $this->timestamp('updated_at', $precision);
    }

    public function softDeletes(string $column = 'deleted_at', int $precision = 0): SchemaColumn
    {
        return $this->timestamp($column, $precision);
    }

    /*
    |--------------------------------------------------------------------------
    | Default PostgreSQL numbers and boolean types
    |--------------------------------------------------------------------------
    */

    public function boolean(string $column): SchemaColumn
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create new small integer (2-byte, -32768 to +32767) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function smallInteger(string $column): SchemaColumn
    {
        return $this->addColumn('smallInteger', $column);
    }

    /**
     * Create new typical integer (4-byte, -2147483648 to +2147483647) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function integer(string $column): SchemaColumn
    {
        return $this->addColumn('integer', $column);
    }

    /**
     * Create new big integer (8-byte, -9223372036854775808 to +9223372036854775807) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function bigInteger(string $column): SchemaColumn
    {
        return $this->addColumn('bigInteger', $column);
    }

    /**
     * Create new auto-incrementing small integer (2-byte, 1 to 32767) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function smallSerial(string $column): SchemaColumn
    {
        return $this->addColumn('smallSerial', $column);
    }

    /**
     * Create new auto-incrementing typical integer (4-byte, 1 to 2147483647) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function serial(string $column): SchemaColumn
    {
        return $this->addColumn('serial', $column);
    }

    /**
     * Create new auto-incrementing typical integer (8-byte, 1 to 9223372036854775807) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function bigSerial(string $column): SchemaColumn
    {
        return $this->addColumn('bigSerial', $column);
    }

    /**
     * Create new double (15 decimal digits precision) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function double(string $column): SchemaColumn
    {
        return $this->addColumn('double', $column);
    }

    /**
     * Create new decimal (up to 131072 digits before decimal point and after 16383) column in table.
     *
     * @param string   $column
     * @param null|int $precision
     * @param null|int $scale
     * @return SchemaColumn
     */
    public function decimal(string $column, int $precision = null, int $scale = null): SchemaColumn
    {
        return $this->numeric($column, $precision, $scale);
    }

    /**
     * Create new numeric (up to 131072 digits before decimal point and after 16383) column in table.
     *
     * @param string   $column
     * @param null|int $precision
     * @param null|int $scale
     * @return SchemaColumn
     */
    public function numeric(string $column, int $precision = null, int $scale = null): SchemaColumn
    {
        return $this->addColumn('numeric', $column, compact('precision', 'scale'));
    }

    /**
     * Create new double (6 decimal digits precision) column in table.
     *
     * @param string $column
     * @return SchemaColumn
     */
    public function real(string $column): SchemaColumn
    {
        return $this->addColumn('real', $column);
    }

    /*
    |--------------------------------------------------------------------------
    | Symbolic types
    |--------------------------------------------------------------------------
    */

    /**
     * Create column as text fixed-length (blank padded).
     *
     * @param string   $column
     * @param null|int $length
     * @return mixed
     */
    public function char(string $column, int $length = null): SchemaColumn
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Create column as text variable-length (with limit).
     *
     * @param string $column
     * @param int|null $length
     * @return mixed
     */
    public function varchar(string $column, int $length = null): SchemaColumn
    {
        return $this->addColumn('varchar', $column, compact('length'));
    }

    /**
     * Create column as text variable unlimited length.
     *
     * @param string $column
     * @return mixed
     */
    public function text(string $column): SchemaColumn
    {
        return $this->addColumn('text', $column);
    }

    /*
    |--------------------------------------------------------------------------
    | Datetime types
    |--------------------------------------------------------------------------
    */

    public function timestamp(string $column, int $precision = 0): SchemaColumn
    {
        if ($precision < 0) {
            $precision = 0;
        }
        if ($precision > 6) {
            $precision = 6;
        }

        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    public function timestampTz(string $column, int $precision = 0): SchemaColumn
    {
        if ($precision < 0) {
            $precision = 0;
        }
        if ($precision > 6) {
            $precision = 6;
        }

        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    public function date(string $column): SchemaColumn
    {
        return $this->addColumn('date', $column);
    }

    public function time(string $column): SchemaColumn
    {
        return $this->addColumn('time', $column);
    }

    public function timeTz(string $column): SchemaColumn
    {
        return $this->addColumn('timeTz', $column);
    }

    public function interval(string $column): SchemaColumn
    {
        throw new DatabaseException("Type 'interval' not supported.");
    }

    /*
    |--------------------------------------------------------------------------
    | UUID
    |--------------------------------------------------------------------------
    */

    public function uuid(string $column): SchemaColumn
    {
        return $this->addColumn('uuid', $column);
    }
}
