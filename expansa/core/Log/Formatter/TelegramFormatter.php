<?php

declare(strict_types=1);

namespace Expansa\Log\Formatter;

use Expansa\Log\LogRecord;

class TelegramFormatter extends BaseFormatter
{
    public function format(LogRecord $record): string
    {
        $format = "[%s] %s.%s: %s %s %s";

        if (isset($record->context['exception']) && is_object($record->context['exception'])) {
            $record->context['exception'] = $this->normalizeException($record->context['exception']);
        }

        return sprintf(
            $format,
            $record->datetime,
            $record->channel,
            $record->levelName,
            $record->message,
            json_encode($record->context),
            json_encode($record->extra)
        );
    }
}