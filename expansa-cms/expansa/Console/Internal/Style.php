<?php

declare(strict_types=1);

namespace Expansa\Console\Internal;

/**
 * Text styles of the `[bold]#text#` markup of the console output, `slow_blink` for SlowBlink.
 *
 * @internal
 * @package Expansa\Console
 */
enum Style: string
{
    case Bold            = "\033[1m";
    case Faint           = "\033[2m";
    case Italic          = "\033[3m";
    case Underline       = "\033[4m";
    case SlowBlink       = "\033[5m";
    case RapidBlink      = "\033[6m";
    case ReverseVideo    = "\033[7m";
    case Conceal         = "\033[8m";
    case CrossedOut      = "\033[9m";
    case PrimaryFont     = "\033[10m";
    case Fraktur         = "\033[20m";
    case DoublyUnderline = "\033[21m";
    case Encircled       = "\033[52m";
}
