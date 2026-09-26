<?php

declare(strict_types=1);

namespace Expansa\Log\Contracts;

use Stringable;
use Expansa\Log\Level;
use Expansa\Log\Exceptions\LogException;

/**
 * PSR-3 logger: `{key}` placeholders of the message are replaced by the context values,
 * an exception is passed in the `exception` key of the context.
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function emergency(string|Stringable $message, array $context = []): void;

    /**
     * Action must be taken immediately.
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function alert(string|Stringable $message, array $context = []): void;

    /**
     * Critical conditions.
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function critical(string|Stringable $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function error(string|Stringable $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function warning(string|Stringable $message, array $context = []): void;

    /**
     * Normal but significant events.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function notice(string|Stringable $message, array $context = []): void;

    /**
     * Interesting events.
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function info(string|Stringable $message, array $context = []): void;

    /**
     * Detailed debug information.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function debug(string|Stringable $message, array $context = []): void;

    /**
     * Logs with an arbitrary level.
     *
     * @param Level|int|string  $level   A level, its value, RFC 5424 code or name.
     * @param string|Stringable $message
     * @param array             $context
     * @throws LogException For an unknown level.
     */
    public function log(Level|int|string $level, string|Stringable $message, array $context = []): void;
}
