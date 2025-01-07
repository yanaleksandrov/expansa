<?php

declare(strict_types=1);

namespace Expansa\Console\Output;

/**
 * Enum Color.
 *
 * Provides color codes for CLI output.
 */
enum Color: string
{
	use EnumFinder;

	case BLACK   = "\033[0;30m";
	case RED     = "\033[0;31m";
	case GREEN   = "\033[0;32m";
	case YELLOW  = "\033[0;33m";
	case BLUE    = "\033[0;34m";
	case MAGENTA = "\033[0;35m";
	case CYAN    = "\033[0;36m";
	case WHITE   = "\033[0;37m";
	case GRAY    = "\033[0;90m";
}
