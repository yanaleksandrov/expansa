<?php

declare(strict_types=1);

namespace Expansa\Console\Exceptions;

use RuntimeException;

/**
 * Thrown when a command can not finish its work.
 *
 * @package Expansa\Console
 */
final class CommandFailed extends RuntimeException {}
