<?php

declare(strict_types=1);

namespace Expansa\Database\Contracts;

use Expansa\Support\Str;
use Throwable;

class QueryException extends DatabaseException
{
    protected ?array $errorInfo;

    public function __construct(
        protected $sql,
        protected array $bindings,
        Throwable $previous
    )
    {
        parent::__construct('', 0, $previous);

        $this->code    = 0;
        $this->message = $this->formatMessage($sql, $bindings, $previous);

        if ($previous instanceof \PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    protected function formatMessage(string $sql, array $bindings, Throwable $previous): string
    {
        return $previous->getMessage() . ' (SQL: ' . Str::replaceArray('?', $bindings, $sql) . ')';
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
