<?php

declare(strict_types=1);

namespace Expansa\Log\Handlers;

use Expansa\Log\Contracts\Formatter;
use Expansa\Log\Contracts\Handler;
use Expansa\Log\Formatters\LineFormatter;
use Expansa\Log\Level;
use Expansa\Log\LogRecord;

/**
 * Base handler: the minimum level and a formatter, LineFormatter by default.
 *
 * @package Expansa\Log\Handlers
 */
abstract class AbstractHandler implements Handler
{
    protected Level $level;

    protected ?Formatter $formatter = null;

    /**
     * Create the handler.
     *
     * @param Level|int|string $level Minimum level of the records to handle.
     */
    public function __construct(Level|int|string $level = Level::Debug)
    {
        $this->level = Level::of($level);
    }

    abstract public function handle(LogRecord $record): bool;

    public function getLevel(): Level
    {
        return $this->level;
    }

    public function isHandling(Level $level): bool
    {
        return $level->value >= $this->level->value;
    }

    public function setFormatter(Formatter $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function getFormatter(): Formatter
    {
        return $this->formatter ??= $this->getDefaultFormatter();
    }

    protected function getDefaultFormatter(): Formatter
    {
        return new LineFormatter();
    }
}
