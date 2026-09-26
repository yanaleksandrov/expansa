<?php

declare(strict_types=1);

namespace Expansa\Log\Formatters;

use Expansa\Log\LogRecord;

/**
 * Telegram message in the HTML parse mode: level and channel in bold, the context in a code block.
 * Every part is escaped and the text is cut to fit the message limit.
 *
 * @package Expansa\Log\Formatters
 */
class TelegramFormatter extends AbstractFormatter
{
    /**
     * Telegram limit of a message, in characters after the entities are parsed.
     */
    public const int MAX_LENGTH = 4096;

    public function format(LogRecord $record): string
    {
        $header  = '<b>' . $record->level->label() . '</b> ' . $this->escape($record->channel);
        $context = $record->context === [] ? '' : $this->toJson($this->normalize($record->context));

        // markup and entities do not count towards the limit, so the raw text is cut before escaping
        $budget  = self::MAX_LENGTH - mb_strlen($record->level->label() . ' ' . $record->channel) - 2;
        $message = mb_substr($record->message, 0, max(0, $budget));
        $context = mb_substr($context, 0, max(0, $budget - mb_strlen($message) - 1));

        return $header . "\n" . $this->escape($message) . ($context === '' ? '' : "\n<pre>" . $this->escape($context) . '</pre>');
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
