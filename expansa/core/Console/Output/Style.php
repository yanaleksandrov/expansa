<?php

declare(strict_types=1);

namespace Expansa\Console\Output;

/**
 * Enum TextStyle.
 *
 * Provides text formatting codes for CLI output.
 */
enum Style: string
{
	use EnumFinder;

	case BOLD             = "\033[1m";
	case FAINT            = "\033[2m";
	case ITALIC           = "\033[3m";
	case UNDERLINE        = "\033[4m";
	case SLOW_BLINK       = "\033[5m";
	case RAPID_BLINK      = "\033[6m";
	case REVERSE_VIDEO    = "\033[7m";
	case CONCEAL          = "\033[8m";
	case CROSSED_OUT      = "\033[9m";
	case PRIMARY_FONT     = "\033[10m";
	case FRAKTUR          = "\033[20m";
	case DOUBLY_UNDERLINE = "\033[21m";
	case ENCIRCLED        = "\033[52m";
}
