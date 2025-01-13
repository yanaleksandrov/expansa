<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Traits;

use Expansa\Database\Schema\Foreign;
use Expansa\Patterns\Fluent;

trait Commands
{
    protected array $commands = [];

    public function create(): static
    {
        $this->addCommand('create');

        return $this;
    }

    public function rename(string $to): static
    {
        $this->addCommand('rename', ['to' => $to]);

        return $this;
    }

    public function drop(): static
    {
        $this->addCommand('drop');

        return $this;
    }

    public function dropIfExists(): static
    {
        $this->addCommand('dropIfExists');

        return $this;
    }

    public function renameColumn(string $from, string $to, string $type = null): static
    {
        $this->addCommand('renameColumn', ['from' => $from, 'to' => $to, 'type' => $type]);

        return $this;
    }

    public function dropColumn(string|array $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->addCommand('dropColumn', compact('columns'));

        return $this;
    }

    public function primary(string|array $columns, string $index = null): static
    {
        $index = $index ?: $this->createIndexName('primary', (array) $columns);

        $this->addCommand('primary', compact('index', 'columns'));

        return $this;
    }

    public function index(string|array $columns, string $index = null): static
    {
        $index = $index ?: $this->createIndexName('index', (array) $columns);

        $this->addCommand('index', compact('index', 'columns'));

        return $this;
    }

    public function unique(string|array $columns, string $index = null): static
    {
        $index = $index ?: $this->createIndexName('unique', (array) $columns);

        $this->addCommand('unique', compact('index', 'columns'));

        return $this;
    }

    public function dropPrimary(string|array $index): static
    {
        return $this->dropIndexCommand(__FUNCTION__, 'primary', $index);
    }

    public function dropIndex(string|array $index): static
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    public function dropUnique(string|array $index): static
    {
        return $this->dropIndexCommand(__FUNCTION__, 'unique', $index);
    }

    public function foreign(string $column): Foreign
    {
        return $this->commands[] = new Foreign(['name' => 'foreign', 'column' => $column]);
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    private function dropIndexCommand(string $command, string $type, string|array $index): static
    {
        if (is_array($index)) {
            $index = $this->createIndexName($type, $index);
        }

        $this->addCommand($command, compact('index'));

        return $this;
    }

    private function addCommand(string $name, array $parameters = []): Fluent
    {
        return $this->commands[] = new Fluent(array_merge(['name' => $name], $parameters));
    }

    private function createIndexName(string $type, array $columns): string
    {
        $index = array_merge([$this->prefix . $this->name], $columns, [$type]);

        $index = strtolower(implode('_', $index));

        return str_replace(['-', '.'], '_', $index);
    }
}
