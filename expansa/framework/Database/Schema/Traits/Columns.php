<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Traits;

use Expansa\Database\Contracts\Types;
use Expansa\Database\Schema\Column;

trait Columns
{
    protected array $columns = [];

    public function addColumn(string $type, string $name, array $parameters = []): Column
    {
        return $this->columns[] = new Column(array_merge(['type' => $type, 'name' => $name], $parameters));
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function id(string $column = 'id'): Column
    {
        return $this->bigInt($column)->autoIncrement()->unsigned();
    }

    public function ulid(string $column = 'ulid'): Column
    {
        $column = $this->string($column, 24)->unique();

        $this->commands[] = $this->addCommand('createUlid', ['column' => $column->getAttributes()]);

        return $column;
    }

    public function tinyInt(string $column, int $precision = 3): Column
    {
        return $this->addColumn(Types::TINY_INTEGER, $column, ['precision' => $precision]);
    }

    public function smallInt(string $column, int $precision = 5): Column
    {
        return $this->addColumn(Types::SMALL_INTEGER, $column, ['precision' => $precision]);
    }

    public function mediumInt(string $column, int $precision = 8): Column
    {
        return $this->addColumn(Types::MEDIUM_INTEGER, $column, ['precision' => $precision]);
    }

    public function int(string $column, int $precision = 10): Column
    {
        return $this->addColumn(Types::INTEGER, $column, ['precision' => $precision]);
    }

    public function bigInt(string $column, int $precision = 20): Column
    {
        return $this->addColumn(Types::BIG_INTEGER, $column, ['precision' => $precision]);
    }

    public function decimal(string $column, int $precision = null, int $scale = null): Column
    {
        return $this->addColumn(Types::DECIMAL, $column, ['precision' => $precision, 'scale' => $scale]);
    }

    public function float(string $column): Column
    {
        return $this->addColumn(Types::FLOAT, $column);
    }

    public function double(string $column): Column
    {
        return $this->addColumn(Types::DOUBLE, $column);
    }

    public function bool(string $column): Column
    {
        return $this->addColumn(Types::BOOLEAN, $column);
    }

    public function blob(string $column): Column
    {
        return $this->addColumn(Types::BLOB, $column);
    }

    public function string(string $column, int $length = null): Column
    {
        return $this->addColumn(Types::STRING, $column, ['length' => $length]);
    }

    public function char(string $column, int $length = null): Column
    {
        return $this->addColumn(Types::CHAR, $column, ['length' => $length]);
    }

    public function tinyText(string $column): Column
    {
        return $this->addColumn(Types::TINY_TEXT, $column);
    }

    public function mediumText(string $column): Column
    {
        return $this->addColumn(Types::MEDIUM_TEXT, $column);
    }

    public function text(string $column): Column
    {
        return $this->addColumn(Types::TEXT, $column);
    }

    public function longText(string $column): Column
    {
        return $this->addColumn(Types::LONG_TEXT, $column);
    }

    public function uuid(string $column): Column
    {
        return $this->addColumn(Types::UUID, $column);
    }

    public function json(string $column): Column
    {
        return $this->addColumn(Types::JSON, $column);
    }

    public function enum(string $column, array $allowed): Column
    {
        return $this->addColumn(Types::ENUM, $column, ['allowed' => $allowed]);
    }

    public function date(string $column): Column
    {
        return $this->addColumn(Types::DATE, $column);
    }

    public function time(string $column): Column
    {
        return $this->addColumn(Types::TIME, $column);
    }

    public function datetime(string $column): Column
    {
        return $this->addColumn(Types::DATETIME, $column);
    }

    public function timestamp(string $column): Column
    {
        return $this->addColumn(Types::TIMESTAMP, $column);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->useCurrent();
        $this->timestamp('updated_at')->useCurrent();
    }
}
