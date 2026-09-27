<?php

declare(strict_types=1);

namespace Expansa\Console\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a command gets an unknown option or an invalid option value.
 *
 * @package Expansa\Console
 */
final class InvalidOption extends InvalidArgumentException {}
