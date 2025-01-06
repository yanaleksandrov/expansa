<?php

declare(strict_types=1);

namespace Expansa\Log\Handlers;

use Expansa\Log\Contracts\Formatter;
use Expansa\Log\Contracts\Handler;
use Expansa\Log\Formatter\LineFormatter;
use Expansa\Log\Logger;
use Expansa\Log\LogRecord;

abstract class AbstractHandler implements Handler
{
    protected int $level;

    protected Formatter $formatter;

    public function __construct(int|string $level)
    {
        $this->level = Logger::toLevel($level);
    }

    public function isHandling(int $level): bool
    {
        return $level >= $this->level;
    }

    abstract public function handle(LogRecord $record): bool;

    public function setFormatter(Formatter $formatter): void
    {
        $this->formatter = $formatter;
    }

    public function getFormatter(): Formatter
    {
        if (! isset($this->formatter)) {
            $this->formatter = $this->getDefaultFormatter();
        }

        return $this->formatter;
    }

    public function getDefaultFormatter(): Formatter
    {
        return new LineFormatter();
    }
}
