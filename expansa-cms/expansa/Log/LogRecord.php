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
    public function __construct(

        /**
         * Moment the record was created.
         */
        public DateTimeImmutable $datetime,

        /**
         * Name of the logger.
         */
        public string $channel,

        /**
         * Severity of the record.
         */
        public Level $level,

        /**
         * With the `{placeholders}` already replaced from the context.
         */
        public string $message,

        /**
         * Context of the record, with the logger context merged in.
         */
        public array $context = [],

        /**
         * Additional data a handler or formatter may print.
         */
        public array $extra = [],
    ) {} // phpcs:ignore
}
