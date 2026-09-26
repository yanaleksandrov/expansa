<?php

declare(strict_types=1);

namespace Expansa\Log\Handlers;

use Expansa\Log\LogRecord;

/**
 * Passes records to error_log(): the web server log or the `error_log` ini file.
 *
 * @package Expansa\Log\Handlers
 */
class ErrorLog extends AbstractHandler
{
    public function handle(LogRecord $record): bool
    {
        return error_log(rtrim($this->getFormatter()->format($record), "\n")); // phpcs:ignore
    }
}
