<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Exceptions;

use RuntimeException;

/**
 * Thrown when a disk operation fails: writing, copying, moving, deleting, changing permissions
 * or downloading a file by URL.
 *
 * @package Expansa\Filesystem\Exceptions
 */
final class OperationFailed extends RuntimeException {}
