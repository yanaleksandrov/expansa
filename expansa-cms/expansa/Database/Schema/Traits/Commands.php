<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Traits;

use Expansa\Database\Schema\CommandForeign;
use Expansa\Patterns\Fluent;

/**
 * Table-level DDL commands (create/rename/drop, indexes, foreign keys) - see
 * {@see \Expansa\Database\Schema\Table}'s own `@method` list for each one's documented signature.
 * Each just records a {@see Fluent} command; {@see \Expansa\Database\Schema\Builder::create()}
 * compiles them into actual SQL afterward.
 */
trait Commands
{
    /**
     * @var array<int, Fluent>
     */
    public array $commands = [];

    /**
     * @return static
     */
    public function create(): static
    {
        $this->addCommand('create');

        return $this;
    }

    /**
     * @param string $to
     * @return static
     */
    public function rename(string $to): static
    {
        $this->addCommand('rename', ['to' => $to]);

        return $this;
    }

    /**
     * @return static
     */
    public function drop(): static
    {
        $this->addCommand('drop');

        return $this;
    }

    /**
     * @return static
     */
    public function dropIfExists(): static
    {
        $this->addCommand('dropIfExists');

        return $this;
    }

    /**
     * @param string      $from
     * @param string      $to
     * @param string|null $type
     * @return static
     */
    public function renameColumn(string $from, string $to, ?string $type = null): static
    {
        $this->addCommand('renameColumn', ['from' => $from, 'to' => $to, 'type' => $type]);

        return $this;
    }

    /**
     * @param string|array<string> $columns One column, or several (as an array, or one per argument).
     * @return static
     */
    public function dropColumn(string|array $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->addCommand('dropColumn', compact('columns'));

        return $this;
    }

    /**
     * @param string|array<string> $columns
     * @param string|null          $index   Index name; auto-generated from $columns if omitted.
     * @return static
     */
    public function primary(string|array $columns, ?string $index = null): static
    {
        $index = $index ?: $this->createIndexName('primary', (array) $columns);

        $this->addCommand('primary', compact('index', 'columns'));

        return $this;
    }

    /**
     * @param string|array<string> $columns
     * @param string|null          $index   Index name; auto-generated from $columns if omitted.
     * @return static
     */
    public function index(string|array $columns, ?string $index = null): static
    {
        $index = $index ?: $this->createIndexName('index', (array) $columns);

        $this->addCommand('index', compact('index', 'columns'));

        return $this;
    }

    /**
     * @param string|array<string> $columns
     * @param string|null          $index   Index name; auto-generated from $columns if omitted.
     * @return static
     */
    public function unique(string|array $columns, ?string $index = null): static
    {
        $index = $index ?: $this->createIndexName('unique', (array) $columns);

        $this->addCommand('unique', compact('index', 'columns'));

        return $this;
    }

    /**
     * A FULLTEXT index — for a text/mediumtext/longtext column that needs MATCH() AGAINST()
     * search instead of exact-match/range lookups. The compiler already knew how to emit one
     * (see Compilers/Indexes.php); this was the only missing piece to reach it from a migration,
     * same shape as index()/unique() above.
     *
     * @param string|array<string> $columns
     * @param string|null          $index   Index name; auto-generated from $columns if omitted.
     * @return static
     */
    public function fulltext(string|array $columns, ?string $index = null): static
    {
        $index = $index ?: $this->createIndexName('fulltext', (array) $columns);

        $this->addCommand('fulltext', compact('index', 'columns'));

        return $this;
    }

    /**
     * @param string|array<string> $index
     * @return static
     */
    public function dropPrimary(string|array $index): static
    {
        return $this->dropIndexCommand(__FUNCTION__, 'primary', $index);
    }

    /**
     * @param string|array<string> $index
     * @return static
     */
    public function dropIndex(string|array $index): static
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * @param string|array<string> $index
     * @return static
     */
    public function dropUnique(string|array $index): static
    {
        return $this->dropIndexCommand(__FUNCTION__, 'unique', $index);
    }

    /**
     * Starts a foreign key definition - chain ->references()->on()->onDelete...()/onUpdate...()
     * on the result (see {@see CommandForeign}) before the table is compiled.
     *
     * @param string $column The local column the constraint is on.
     * @return CommandForeign
     */
    public function foreign(string $column): CommandForeign
    {
        return $this->commands[] = new CommandForeign(['name' => 'foreign', 'column' => $column]);
    }

    /**
     * Shared body of dropPrimary()/dropIndex()/dropUnique(): resolves $index to a name (deriving
     * one from column names if an array was given) and records the drop command.
     *
     * @param string                $command Command name to record ('dropIndex', 'dropPrimary', 'dropUnique').
     * @param string                $type    Index type, for name generation - see {@see self::createIndexName()}.
     * @param string|array<string>  $index   An explicit index name, or the column(s) to derive one from.
     * @return static
     */
    private function dropIndexCommand(string $command, string $type, string|array $index): static
    {
        if (is_array($index)) {
            $index = $this->createIndexName($type, $index);
        }

        $this->addCommand($command, compact('index'));

        return $this;
    }

    /**
     * @param string               $name
     * @param array<string, mixed> $parameters
     * @return Fluent
     */
    private function addCommand(string $name, array $parameters = []): Fluent
    {
        return $this->commands[] = new Fluent(array_merge(['name' => $name], $parameters));
    }

    /**
     * Derives an index name from its type and columns, e.g. ['email'], 'unique' -> "email_unique".
     *
     * @param string        $type
     * @param array<string> $columns
     * @return string
     */
    private function createIndexName(string $type, array $columns): string
    {
        $index = strtolower(implode('_', array_merge($columns, [$type])));

        return str_replace(['-', '.'], '_', $index);
    }
}
