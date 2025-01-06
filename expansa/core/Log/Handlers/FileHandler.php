<?php

declare(strict_types=1);

namespace Expansa\Log\Handlers;

use Expansa\Log\LogRecord;

class FileHandler extends AbstractHandler
{
    public function __construct(protected string $path, int|string $level)
    {
        parent::__construct($level);
    }

    public function handle(LogRecord $record): bool
    {
        return $this->write($this->getFormatter()->format($record));
    }

    protected function write(string $message): bool
    {
        return file_put_contents($this->path, $message, FILE_APPEND) !== false;
    }
}