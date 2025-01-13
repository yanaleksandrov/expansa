<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Events;

use Expansa\DatabaseLegacy\Contracts\Connection;

class QueryExecuted
{
    public function __construct(
        public string $sql,
        public array $bindings,
        public float $time,
        public Connection $connection
    ) {} // phpcs:ignore
}
