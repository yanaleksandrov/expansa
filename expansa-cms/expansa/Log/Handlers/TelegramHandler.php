<?php

declare(strict_types=1);

namespace Expansa\Log\Handlers;

use Expansa\Log\Contracts\Formatter;
use Expansa\Log\Formatters\TelegramFormatter;
use Expansa\Log\Level;
use Expansa\Log\LogRecord;

/**
 * Sends records to a Telegram chat through a bot. Each record is an HTTP request,
 * so keep the level high, `error` by default.
 *
 * @package Expansa\Log\Handlers
 */
class TelegramHandler extends AbstractHandler
{
    /**
     * Create the handler.
     *
     * @param string           $token   Bot token.
     * @param int|string       $chatId  Chat id, or `@username` of a channel.
     * @param Level|int|string $level
     * @param int              $timeout Seconds to wait for Telegram, so a slow API does not hang the request.
     */
    public function __construct(
        protected readonly string $token,
        protected readonly int|string $chatId,
        Level|int|string $level = Level::Error,
        protected readonly int $timeout = 5
    )
    {
        parent::__construct($level);
    }

    public function handle(LogRecord $record): bool
    {
        return $this->send($this->getFormatter()->format($record));
    }

    protected function getDefaultFormatter(): Formatter
    {
        return new TelegramFormatter();
    }

    /**
     * Send a message through the Bot API.
     *
     * @param string $message HTML formatted text.
     * @return bool True if Telegram accepted the message.
     */
    protected function send(string $message): bool
    {
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/sendMessage');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_POSTFIELDS     => [
                'chat_id'                  => $this->chatId,
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => 'true',
            ],
        ]);

        $response = curl_exec($ch);

        return is_string($response) && (json_decode($response, true)['ok'] ?? false) === true;
    }
}
