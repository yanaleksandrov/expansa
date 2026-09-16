<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Traits;

use Expansa\Database\Schema\Column;

/**
 * One method per column type - see {@see \Expansa\Database\Schema\Table}'s own `@method` list for
 * the full, documented signature of each; most just forward their name and args to addColumn().
 */
trait Columns
{
    /**
     * @var array<int, Column>
     */
    public array $columns = [];

    /**
     * The primitive every other method here forwards to - builds and registers a Column of $type.
     *
     * @param string               $type
     * @param string               $name
     * @param array<string, mixed> $parameters
     * @return Column
     */
    public function addColumn(string $type, string $name, array $parameters = []): Column
    {
        return $this->columns[] = new Column(array_merge(['type' => $type, 'name' => $name], $parameters));
    }

    /**
     * Auto-incrementing, unsigned BIGINT primary key.
     *
     * @param string $column
     * @return Column
     */
    public function id(string $column = 'id'): Column
    {
        return $this->bigInt($column)->autoIncrement()->unsigned();
    }

    /**
     * CHAR(31), unique, auto-populated on insert via a BEFORE INSERT trigger (registered as a
     * 'createUlid' command - see {@see \Expansa\Database\Schema\Compilers\Triggers}).
     *
     * @param string $column
     * @return Column
     */
    public function ulid(string $column = 'ulid'): Column
    {
        $column = $this->addColumn('char(31)', $column)->unique();

        $this->commands[] = $this->addCommand('createUlid', ['column' => $column->getAttributes()]);

        return $column;
    }

    /**
     * CHAR(36), auto-populated on insert via a BEFORE INSERT trigger (registered as a
     * 'createUuid' command - see {@see \Expansa\Database\Schema\Compilers\Triggers}).
     *
     * @param string $column
     * @return Column
     */
    public function uuid(string $column = 'uuid'): Column
    {
        $column = $this->addColumn('char(36)', $column);

        $this->commands[] = $this->addCommand('createUuid', ['column' => $column->getAttributes()]);

        return $column;
    }

    /**
     * @param string $column
     * @param int    $precision
     * @return Column
     */
    public function tinyInt(string $column, int $precision = 3): Column
    {
        return $this->addColumn('tinyint', $column, ['precision' => $precision]);
    }

    /**
     * @param string $column
     * @param int    $precision
     * @return Column
     */
    public function smallInt(string $column, int $precision = 5): Column
    {
        return $this->addColumn('smallint', $column, ['precision' => $precision]);
    }

    /**
     * @param string $column
     * @param int    $precision
     * @return Column
     */
    public function mediumInt(string $column, int $precision = 8): Column
    {
        return $this->addColumn('mediumint', $column, ['precision' => $precision]);
    }

    /**
     * @param string $column
     * @param int    $precision
     * @return Column
     */
    public function int(string $column, int $precision = 10): Column
    {
        return $this->addColumn('int', $column, ['precision' => $precision]);
    }

    /**
     * @param string $column
     * @param int    $precision
     * @return Column
     */
    public function bigInt(string $column, int $precision = 20): Column
    {
        return $this->addColumn('bigint', $column, ['precision' => $precision]);
    }

    /**
     * @param string   $column
     * @param int|null $precision
     * @param int|null $scale
     * @return Column
     */
    public function decimal(string $column, ?int $precision = null, ?int $scale = null): Column
    {
        return $this->addColumn('decimal', $column, ['precision' => $precision, 'scale' => $scale]);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function float(string $column): Column
    {
        return $this->addColumn('float', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function double(string $column): Column
    {
        return $this->addColumn('double', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function bool(string $column): Column
    {
        return $this->addColumn('tinyint(1)', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function blob(string $column): Column
    {
        return $this->addColumn('blob', $column);
    }

    /**
     * @param string   $column
     * @param int|null $length
     * @return Column
     */
    public function string(string $column, ?int $length = null): Column
    {
        return $this->addColumn('varchar', $column, ['length' => $length]);
    }

    /**
     * @param string   $column
     * @param int|null $length
     * @return Column
     */
    public function char(string $column, ?int $length = null): Column
    {
        return $this->addColumn('char', $column, ['length' => $length]);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function tinyText(string $column): Column
    {
        return $this->addColumn('tinytext', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function mediumText(string $column): Column
    {
        return $this->addColumn('mediumtext', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function text(string $column): Column
    {
        return $this->addColumn('text', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function longText(string $column): Column
    {
        return $this->addColumn('longtext', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function json(string $column): Column
    {
        return $this->addColumn('json', $column);
    }

    /**
     * @param string        $column
     * @param array<string> $allowed
     * @return Column
     */
    public function enum(string $column, array $allowed): Column
    {
        return $this->addColumn('enum', $column, ['allowed' => $allowed]);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function date(string $column): Column
    {
        return $this->addColumn('date', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function time(string $column): Column
    {
        return $this->addColumn('time', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function datetime(string $column): Column
    {
        return $this->addColumn('datetime', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function timestamp(string $column): Column
    {
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Adds created_at/updated_at timestamp columns, both defaulting to CURRENT_TIMESTAMP.
     *
     * @return void
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->useCurrent();
        $this->timestamp('updated_at')->useCurrent();
    }
}
