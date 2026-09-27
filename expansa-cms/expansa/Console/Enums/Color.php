<?php

declare(strict_types=1);

namespace Expansa\Console\Enums;

use Expansa\Console\Traits\FindsByName;

/**
 * Text colors of the `[green]#text#` markup of the console output.
 *
 * @package Expansa\Console
 */
enum Color: string
{
    use FindsByName;

    case Black   = "\033[0;30m";
    case Red     = "\033[0;31m";
    case Green   = "\033[0;32m";
    case Yellow  = "\033[0;33m";
    case Blue    = "\033[0;34m";
    case Magenta = "\033[0;35m";
    case Cyan    = "\033[0;36m";
    case White   = "\033[0;37m";
    case Gray    = "\033[0;90m";
}
