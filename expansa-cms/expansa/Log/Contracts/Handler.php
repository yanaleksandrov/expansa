<?php

declare(strict_types=1);

namespace Expansa\Log\Contracts;

use Expansa\Log\Enums\Level;
use Expansa\Log\Record;

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
     * @param Record $record
     * @return bool False if the record was not written.
     */
    public function handle(Record $record): bool;
}
