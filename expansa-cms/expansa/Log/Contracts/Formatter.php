<?php

declare(strict_types=1);

namespace Expansa\Log\Contracts;

use Expansa\Log\LogRecord;

interface Formatter
{
    /**
     * Turn a record into the text a handler writes or sends.
     *
     * @param LogRecord $record
     * @return string
     */
    public function format(LogRecord $record): string;
}
