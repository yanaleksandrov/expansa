<?php

declare(strict_types=1);

namespace Expansa\Log\Contracts;

use Expansa\Log\Level;
use Expansa\Log\LogRecord;

interface Handler
{
    /**
     * Minimum level the handler writes.
     */
    public Level $level { get; }

    /**
     * Check if the handler writes records of a level.
     *
     * @param Level $level
     * @return bool
     */
    public function isHandling(Level $level): bool;

    /**
     * Write or send a record, the logger checks isHandling() first.
     *
     * @param LogRecord $record
     * @return bool False if the record was not written.
     */
    public function handle(LogRecord $record): bool;
}
