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
     * CHAR(36) for a UUID; the model fills it on insert, see {@see \Expansa\Database\Traits\HasUuid}.
     *
     * @param string $column
     * @return Column
     */
    public function uuid(string $column = 'uuid'): Column
    {
        return $this->addColumn('char(36)', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function tinyInt(string $column): Column
    {
        return $this->addColumn('tinyint', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function smallInt(string $column): Column
    {
        return $this->addColumn('smallint', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function mediumInt(string $column): Column
    {
        return $this->addColumn('mediumint', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function int(string $column): Column
    {
        return $this->addColumn('int', $column);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function bigInt(string $column): Column
    {
        return $this->addColumn('bigint', $column);
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
     * @param string $column
     * @param int    $length
     * @return Column
     */
    public function string(string $column, int $length = 255): Column
    {
        return $this->addColumn('varchar', $column, ['length' => $length]);
    }

    /**
     * @param string $column
     * @param int    $length
     * @return Column
     */
    public function char(string $column, int $length = 255): Column
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
     * Adds created_at/updated_at DATETIME columns, both defaulting to CURRENT_TIMESTAMP:
     * unlike TIMESTAMP, they hold dates after 2038 and are not converted by the session time zone.
     *
     * @return void
     */
    public function timestamps(): void
    {
        $this->datetime('created_at')->useCurrent();
        $this->datetime('updated_at')->useCurrent();
    }
}
