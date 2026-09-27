<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Exceptions;

use RuntimeException;

/**
 * Thrown when a file or a directory to copy, move or rename does not exist.
 *
 * @package Expansa\Filesystem\Exceptions
 */
final class NotFound extends RuntimeException {}
