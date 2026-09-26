<?php

declare(strict_types=1);

namespace Expansa\Log\Contracts;

use Expansa\Log\Level;
use Expansa\Log\LogRecord;

interface Handler
{
    /**
     * Get the minimum level the handler writes.
     *
     * @return Level
     */
    public function getLevel(): Level;

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
