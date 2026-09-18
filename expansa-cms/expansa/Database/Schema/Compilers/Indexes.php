<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Query\Builder;
use Expansa\Database\Schema\Table;
use Expansa\Patterns\Fluent;

trait Indexes
{
    /**
     * Real actions MySQL's FOREIGN KEY ... ON DELETE/ON UPDATE accepts - anything else falls
     * back to RESTRICT rather than let an arbitrary string reach the DDL statement.
     *
     * @var array<string>
     */
    private const array FOREIGN_KEY_ACTIONS = ['CASCADE', 'RESTRICT', 'SET NULL', 'SET DEFAULT', 'NO ACTION'];

    /**
     * Compiles one ALTER TABLE ADD statement for an index-shaped command (primary/index/unique/
     * fulltext/spatial), or delegates to {@see self::compileForeignKey()} for a 'foreign' one.
     * Any other command name compiles to "" - it isn't this method's concern.
     *
     * @param Table  $table   The table the command was declared on.
     * @param Fluent $command One entry from $table->commands.
     * @return string
     */
    protected function compileIndexes(Table $table, Fluent $command): string
    {
        if ($command->name === 'foreign') {
            return $this->compileForeignKey($table, $command);
        }

        $index = match ($command->name) {
            'primary'  => 'PRIMARY KEY',
            'index'    => 'INDEX',
            'unique'   => 'UNIQUE',
            'fulltext' => 'FULLTEXT INDEX',
            'spatial'  => 'SPATIAL INDEX',
            default    => '',
        };

        if (!$index) {
            return '';
        }

        $existsColumns  = array_map(fn($column) => $column->getAttributes(), $table->columns);
        $existsLength   = array_column($existsColumns, 'length', 'name');
        $existsColumns  = array_column($existsColumns, 'type', 'name');
        $indexedColumns = (array) ($command->getAttributes()['columns'] ?? []);

        $columns = array_intersect_key($existsColumns, array_flip($indexedColumns));

        $keys = [];

        $isHasIndexLength = false;
        foreach ($columns as $column => $type) {
            $isTextColumnType = preg_match('/^(char|varchar|text|tinytext|mediumtext|longtext)/', $type);
            $textColumnLength = intval($existsLength[$column] ?? 0);

            if ($isTextColumnType) {
                if (
                    (
                        ($index === 'UNIQUE' && ! $isHasIndexLength)
                        ||
                        ($index !== 'UNIQUE')
                    )
                    &&
                    $textColumnLength > Builder::MAX_INDEXABLE_LENGTH
                ) {
                    $keys[] = sprintf("`$column`(%d)", Builder::MAX_INDEXABLE_LENGTH);
                    if ($index === 'UNIQUE') {
                        $isHasIndexLength = true;
                    }
                } else {
                    $keys[] = "`$column`";
                }
            } else {
                $keys[] = "`$column`";
            }
        }

        return sprintf("ALTER TABLE <$table->name> ADD $index <$command->index> (%s)", implode(', ', $keys));
    }

    /**
     * Compiles a real `FOREIGN KEY` constraint from a {@see \Expansa\Database\Schema\CommandForeign}
     * command (`$table->foreign($column)->references(...)->on(...)->onDelete...()`) - this used to
     * be faked with a hand-rolled `AFTER DELETE` trigger (see git history), which only ever cleaned
     * up one hardcoded table, ignored whichever onDelete/onUpdate action was actually configured,
     * and - since every trigger was named after the *parent* table regardless of which child table
     * it belonged to - would fail with "trigger already exists" the moment a second foreign()
     * targeting the same parent was compiled (exactly what {@see \Expansa\Database\FieldEavTyped}'s
     * five sibling tables each do). A real constraint has none of these problems: MySQL enforces
     * it natively, the constraint name is scoped to $table->name (always unique per child table),
     * and ON DELETE/ON UPDATE do what they say.
     *
     * @param Table  $table   The child table the constraint is added to.
     * @param Fluent $command A 'foreign' command from $table->commands.
     * @return string "" if $command is missing on()/column/references() (nothing to compile).
     */
    protected function compileForeignKey(Table $table, Fluent $command): string
    {
        if (!isset($command->on, $command->column, $command->references)) {
            return '';
        }

        $onDelete = in_array($command->onDelete, self::FOREIGN_KEY_ACTIONS, true) ? $command->onDelete : 'RESTRICT';
        $onUpdate = in_array($command->onUpdate, self::FOREIGN_KEY_ACTIONS, true) ? $command->onUpdate : 'RESTRICT';
        $name     = "fk_{$table->name}_{$command->column}";

        return "ALTER TABLE <$table->name> ADD CONSTRAINT `$name` "
            . "FOREIGN KEY (`$command->column`) REFERENCES <$command->on> (`$command->references`) "
            . "ON DELETE $onDelete ON UPDATE $onUpdate";
    }
}
