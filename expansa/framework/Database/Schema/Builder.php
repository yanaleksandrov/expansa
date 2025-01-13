<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Closure;
use Expansa\Database\Query\Builder as QueryBuilder;

class Builder extends BuilderGrammar
{
    public function __construct(
        public QueryBuilder $connection,
    ) {} // phpcs:ignore

    public function create(string $name, Closure $callback): void
    {
        $this->bind(
            "CREATE TABLE IF NOT EXISTS {$this->wrap($name)} (%s) ENGINE=InnoDB DEFAULT{$this->charset()}{$this->collate()}",
            $name,
            $callback
        );
    }

    public function drop(string $name): void
    {
        $this->bind("DROP DATABASE IF EXISTS {$this->wrap($name)}", $name);
    }

    public function rename(string $name, string $to): void
    {
        $this->bind("RENAME TABLE {$this->wrap($name)} TO {$this->wrap($to)}", $name);
    }

    private function bind(string $query, string $name, ?Closure $callback = null): void
    {
        if ($callback instanceof Closure) {
            $callback($table = new Table($name, $this->connection));

            $query = sprintf($query, $this->sql($table));
        }
        var_dump($query . PHP_EOL);
        $this->connection->query($query);
    }
}
