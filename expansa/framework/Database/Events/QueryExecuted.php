<?php declare(strict_types=1);

namespace Expansa\Database\Events;

use Expansa\Database\Contracts\Connection;

class QueryExecuted
{
    public function __construct(
        public string $sql,
        public array $bindings,
        public float $time,
        public Connection $connection
    )
    {}
}