<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Schema;

use Expansa\DatabaseLegacy\Abstracts\GrammarBase;
use Expansa\DatabaseLegacy\Contracts\SchemaGrammar as SchemaGrammarContract;

abstract class Grammar extends GrammarBase implements SchemaGrammarContract
{
    protected bool $transaction = false;

    /**
     * The possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = ['Collate', 'Primary', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
     *
     * @var string[]
     */
    protected array $serials = ['Integer'];

    public function getColumns($table): array
    {
        $columns = [];

        foreach ($table->getColumns() as $column) {
            $sql = $this->wrap($column->name) . ' ' . $this->getType($column);

            $columns[] = $this->addModifiers($sql, $table, $column);
        }

        return $columns;
    }

    public function getType($column): string
    {
        return $this->{'type' . ucfirst($column->type)}($column);
    }

    public function addModifiers($sql, $table, $column): string
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $sql .= $this->{$method}($column);
            }
        }

        return $sql;
    }

    public function supportTransactions(): bool
    {
        return $this->transaction;
    }
}
