<?php

declare(strict_types=1);

namespace Expansa\Log\Formatter;

use Expansa\Log\Contracts\Formatter;
use Expansa\Log\LogRecord;

abstract class BaseFormatter implements Formatter
{
    abstract public function format(LogRecord $record): string;

    public function formatBatch(array $records): array
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }
        return $records;
    }

    protected function normalizeException(\Throwable $e): array
    {
        return [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'code' => $e->getCode(),
        ];
    }
}