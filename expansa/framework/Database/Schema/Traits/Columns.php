<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Traits;

use Expansa\Database\Schema\Column;

trait Columns
{
    public array $columns = [];

    public function addColumn(string $type, string $name, array $parameters = []): Column
    {
        return $this->columns[] = new Column(array_merge(['type' => $type, 'name' => $name], $parameters));
    }

    public function id(string $column = 'id'): Column
    {
        return $this->bigInt($column)->autoIncrement()->unsigned();
    }

    public function ulid(string $column = 'ulid'): Column
    {
        $column = $this->addColumn('char(24)', $column)->unique();

        $this->commands[] = $this->addCommand('createUlid', ['column' => $column->getAttributes()]);

        return $column;
    }

    public function uuid(string $column): Column
    {
        return $this->addColumn('char(36)', $column);
    }

    public function tinyInt(string $column, int $precision = 3): Column
    {
        return $this->addColumn('tinyint', $column, ['precision' => $precision]);
    }

    public function smallInt(string $column, int $precision = 5): Column
    {
        return $this->addColumn('smallint', $column, ['precision' => $precision]);
    }

    public function mediumInt(string $column, int $precision = 8): Column
    {
        return $this->addColumn('mediumint', $column, ['precision' => $precision]);
    }

    public function int(string $column, int $precision = 10): Column
    {
        return $this->addColumn('int', $column, ['precision' => $precision]);
    }

    public function bigInt(string $column, int $precision = 20): Column
    {
        return $this->addColumn('bigint', $column, ['precision' => $precision]);
    }

    public function decimal(string $column, int $precision = null, int $scale = null): Column
    {
        return $this->addColumn('decimal', $column, ['precision' => $precision, 'scale' => $scale]);
    }

    public function float(string $column): Column
    {
        return $this->addColumn('float', $column);
    }

    public function double(string $column): Column
    {
        return $this->addColumn('double', $column);
    }

    public function bool(string $column): Column
    {
        return $this->addColumn('tinyint(1)', $column);
    }

    public function blob(string $column): Column
    {
        return $this->addColumn('blob', $column);
    }

    public function string(string $column, int $length = null): Column
    {
        return $this->addColumn('varchar', $column, ['length' => $length]);
    }

    public function char(string $column, int $length = null): Column
    {
        return $this->addColumn('char', $column, ['length' => $length]);
    }

    public function tinyText(string $column): Column
    {
        return $this->addColumn('tinytext', $column);
    }

    public function mediumText(string $column): Column
    {
        return $this->addColumn('mediumtext', $column);
    }

    public function text(string $column): Column
    {
        return $this->addColumn('text', $column);
    }

    public function longText(string $column): Column
    {
        return $this->addColumn('longtext', $column);
    }

    public function json(string $column): Column
    {
        return $this->addColumn('text', $column);
    }

    public function enum(string $column, array $allowed): Column
    {
        return $this->addColumn('enum', $column, ['allowed' => $allowed]);
    }

    public function date(string $column): Column
    {
        return $this->addColumn('date', $column);
    }

    public function time(string $column): Column
    {
        return $this->addColumn('time', $column);
    }

    public function datetime(string $column): Column
    {
        return $this->addColumn('datetime', $column);
    }

    public function timestamp(string $column): Column
    {
        return $this->addColumn('timestamp', $column);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->useCurrent();
        $this->timestamp('updated_at')->useCurrent();
    }
}
