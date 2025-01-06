<?php

declare(strict_types=1);

namespace Expansa\Log\Contracts;

use Expansa\Log\LogRecord;

interface Formatter
{
    public function format(LogRecord $record): string;

    public function formatBatch(array $records): array;
}
