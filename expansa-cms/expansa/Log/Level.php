<?php

declare(strict_types=1);

namespace Expansa\Log;

use Expansa\Log\Exceptions\LogException;

/**
 * Log levels of RFC 5424 with the Monolog values, a higher value is more severe.
 *
 * @package Expansa\Log
 */
enum Level: int
{
    case Debug     = 100;
    case Info      = 200;
    case Notice    = 250;
    case Warning   = 300;
    case Error     = 400;
    case Critical  = 500;
    case Alert     = 550;
    case Emergency = 600;

    /**
     * RFC 5424 severity codes, from 0 (emergency) to 7 (debug).
     */
    private const array RFC_5424 = [
        0 => self::Emergency,
        1 => self::Alert,
        2 => self::Critical,
        3 => self::Error,
        4 => self::Warning,
        5 => self::Notice,
        6 => self::Info,
        7 => self::Debug,
    ];

    /**
     * Get a level by its case, value, RFC 5424 code or PSR-3 name in any case.
     *
     * @param Level|int|string $level E.g. `Level::Error`, `400`, `3` or `'error'`.
     * @return self
     * @throws LogException
     */
    public static function of(Level|int|string $level): self
    {
        if ($level instanceof self) {
            return $level;
        }

        if (is_int($level)) {
            return self::tryFrom($level) ?? self::RFC_5424[$level] ?? throw self::unknown($level);
        }

        return match (strtolower($level)) {
            'debug'     => self::Debug,
            'info'      => self::Info,
            'notice'    => self::Notice,
            'warning'   => self::Warning,
            'error'     => self::Error,
            'critical'  => self::Critical,
            'alert'     => self::Alert,
            'emergency' => self::Emergency,
            default     => throw self::unknown($level),
        };
    }

    /**
     * Get the upper case name used in log lines, e.g. `ERROR`.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Debug     => 'DEBUG',
            self::Info      => 'INFO',
            self::Notice    => 'NOTICE',
            self::Warning   => 'WARNING',
            self::Error     => 'ERROR',
            self::Critical  => 'CRITICAL',
            self::Alert     => 'ALERT',
            self::Emergency => 'EMERGENCY',
        };
    }

    /**
     * Check if the level is at least as severe as another one.
     *
     * @param Level $level
     * @return bool
     */
    public function includes(Level $level): bool
    {
        return $level->value >= $this->value;
    }

    private static function unknown(int|string $level): LogException
    {
        $names = implode(', ', array_map(fn (self $case) => strtolower($case->name), self::cases()));

        return new LogException("Level \"$level\" is not defined, use one of: $names");
    }
}
