<?php

declare(strict_types=1);

namespace Expansa\Log\Formatters;

use Expansa\Log\LogRecord;

/**
 * One line per record: `[2025-01-01 10:00:00] channel.LEVEL: message {"context"} {"extra"}`.
 * Empty context and extra are omitted.
 *
 * @package Expansa\Log\Formatters
 */
class LineFormatter extends AbstractFormatter
{
    public function __construct(

        /**
         * Format of the record date, see date().
         */
        protected readonly string $dateFormat = 'Y-m-d H:i:s',
    ) {} // phpcs:ignore

    public function format(LogRecord $record): string
    {
        $line = '[' . $record->datetime->format($this->dateFormat) . '] '
            . $record->channel . '.' . $record->level->label() . ': ' . $record->message;

        if ($record->context !== []) {
            $line .= ' ' . $this->toJson($this->normalize($record->context));
        }

        if ($record->extra !== []) {
            $line .= ' ' . $this->toJson($this->normalize($record->extra));
        }

        return $line . "\n";
    }
}
