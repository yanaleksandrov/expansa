<?php

namespace Expansa\Http\Exception;

use InvalidArgumentException;

/**
 * Exception for an invalid argument passed.
 *
 * @package Expansa\Http
 */
final class InvalidArgument extends InvalidArgumentException
{
    /**
     * Create a new invalid argument exception with a standardized text.
     *
     * @param int    $position The argument position in the function signature. 1-based.
     * @param string $name     The argument name in the function signature.
     * @param string $expected The argument type expected as a string.
     * @param string $received The actual argument type received.
     *
     * @return InvalidArgument
     */
    public static function create(int $position, string $name, string $expected, string $received): InvalidArgument
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return new self(
            sprintf(
                '%s::%s(): Argument #%d (%s) must be of type %s, %s given',
                $stack[1]['class'],
                $stack[1]['function'],
                $position,
                $name,
                $expected,
                $received
            )
        );
    }
}
