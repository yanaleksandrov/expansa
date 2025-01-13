<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Closure;
use Expansa\Database\Connector\Manager;

class Builder extends Grammar
{
    public function __construct(
        public Manager $connection,
    ) {} // phpcs:ignore

    public function create(string $name, Closure $callback): void
    {
        $this->bind("CREATE DATABASE {$this->wrap($name)} (%s) DEFAULT{$this->charset()}{$this->collate()}", $callback);
    }

    public function createIfNotExists(string $name, Closure $callback): void
    {
        $this->bind(
            "CREATE TABLE IF NOT EXISTS {$this->wrap($name)} (%s) DEFAULT{$this->charset()}{$this->collate()}",
            $callback
        );
    }

    public function drop(string $name): void
    {
        $this->bind("DROP DATABASE {$this->wrap($name)}");
    }

    public function dropIfExists(string $name): void
    {
        $this->bind("DROP DATABASE IF EXISTS {$this->wrap($name)}");
    }

    public function rename(string $name, string $to): void
    {
        $this->bind("RENAME TABLE {$this->wrap($name)} TO {$this->wrap($to)}");
    }

    public function bind(string $query, ?Closure $callback = null): void
    {
        if ($callback instanceof Closure) {
            $callback($table = new Table());

            $query = sprintf($query, $this->sql($table));
        }
        var_dump($query . PHP_EOL);
        $this->connection->query($query);
    }
}
