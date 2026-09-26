<?php

declare(strict_types=1);

namespace Expansa\Log;

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use Stringable;
use Throwable;
use Expansa\Log\Contracts\Handler;
use Expansa\Log\Contracts\LoggerInterface;

/**
 * PSR-3 logger of one channel: builds a record and passes it to every handler of its level.
 * A record below the level of all handlers costs a single comparison.
 *
 * @package Expansa\Log
 */
class Logger implements LoggerInterface
{
    /**
     * Handlers, the top of the stack first.
     *
     * @var Handler[]
     */
    protected array $handlers = [];

    /**
     * Context added to every record, the record context overrides its keys.
     */
    protected array $context = [];

    /**
     * Lowest level of the handlers, PHP_INT_MAX without handlers.
     */
    private int $minLevel = PHP_INT_MAX;

    /**
     * Create a logger.
     *
     * @param string    $name     Channel name, printed in every record.
     * @param Handler[] $handlers
     */
    public function __construct(
        protected readonly string $name = 'app',
        array $handlers = []
    )
    {
        $this->setHandlers($handlers);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add a handler on top of the stack.
     *
     * @param Handler $handler
     * @return static
     */
    public function pushHandler(Handler $handler): static
    {
        array_unshift($this->handlers, $handler);

        return $this->setHandlers($this->handlers);
    }

    /**
     * Remove the handler on top of the stack.
     *
     * @return Handler
     * @throws LogicException If there are no handlers.
     */
    public function popHandler(): Handler
    {
        if ($this->handlers === []) {
            throw new LogicException('You tried to pop from an empty handler stack.');
        }

        $handler = array_shift($this->handlers);
        $this->setHandlers($this->handlers);

        return $handler;
    }

    /**
     * Replace all handlers.
     *
     * @param Handler[] $handlers
     * @return static
     */
    public function setHandlers(array $handlers): static
    {
        $this->handlers = array_values($handlers);
        $this->minLevel = PHP_INT_MAX;

        foreach ($this->handlers as $handler) {
            $this->minLevel = min($this->minLevel, $handler->getLevel()->value);
        }

        return $this;
    }

    /**
     * Get the handlers, the top of the stack first.
     *
     * @return Handler[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Check if any handler writes records of a level.
     *
     * @param Level|int|string $level
     * @return bool
     */
    public function isHandling(Level|int|string $level): bool
    {
        return Level::of($level)->value >= $this->minLevel;
    }

    /**
     * Add context to every following record.
     *
     * @param array $context
     * @return static
     */
    public function withContext(array $context): static
    {
        $this->context = [...$this->context, ...$context];

        return $this;
    }

    public function withoutContext(): static
    {
        $this->context = [];

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function log(Level|int|string $level, string|Stringable $message, array $context = []): void
    {
        $level = Level::of($level);
        if ($level->value < $this->minLevel) {
            return;
        }

        if ($this->context !== []) {
            $context = [...$this->context, ...$context];
        }

        $message = self::interpolate((string) $message, $context);
        $record  = new LogRecord(new DateTimeImmutable(), $this->name, $level, $message, $context);

        foreach ($this->handlers as $handler) {
            if (! $handler->isHandling($level)) {
                continue;
            }

            // a broken handler must neither break the application nor stop the other handlers
            try {
                $handler->handle($record);
            } catch (Throwable $e) {
                error_log('Log handler ' . $handler::class . ' failed: ' . $e->getMessage() . '; record: ' . $record->message); // phpcs:ignore
            }
        }
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Emergency, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Alert, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Critical, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Error, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Warning, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Notice, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Info, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(Level::Debug, $message, $context);
    }

    /**
     * Replace `{key}` placeholders with the context values that can be printed.
     *
     * @param string $message
     * @param array  $context
     * @return string
     */
    private static function interpolate(string $message, array $context): string
    {
        if ($context === [] || ! str_contains($message, '{')) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = match (true) {
                $value === null                      => 'null',
                is_bool($value)                      => $value ? 'true' : 'false',
                is_scalar($value)                    => (string) $value,
                $value instanceof Throwable          => $value::class . ': ' . $value->getMessage(),
                $value instanceof Stringable         => (string) $value,
                $value instanceof DateTimeInterface  => $value->format(DateTimeInterface::RFC3339),
                default                              => '[' . get_debug_type($value) . ']',
            };
        }

        return strtr($message, $replace);
    }
}
