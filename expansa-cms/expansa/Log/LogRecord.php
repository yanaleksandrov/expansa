<?php

declare(strict_types=1);

namespace Expansa\Log;

use DateTimeImmutable;

/**
 * One log entry, shared by every handler of the logger, so it is immutable.
 *
 * @package Expansa\Log
 */
final readonly class LogRecord
{
    /**
     * Create the record.
     *
     * @param DateTimeImmutable $datetime
     * @param string            $channel  Name of the logger.
     * @param Level             $level
     * @param string            $message  With the `{placeholders}` already replaced from the context.
     * @param array             $context
     * @param array             $extra    Additional data a handler or formatter may print.
     */
    public function __construct(
        public DateTimeImmutable $datetime,
        public string $channel,
        public Level $level,
        public string $message,
        public array $context = [],
        public array $extra = []
    ) {} // phpcs:ignore
}
