<?php

declare(strict_types=1);

namespace Expansa\Log\Exceptions;

use RuntimeException;

/**
 * Thrown when the log file or its directory can not be created or opened.
 *
 * @package Expansa\Log\Exceptions
 */
final class UnwritableFile extends RuntimeException {}
