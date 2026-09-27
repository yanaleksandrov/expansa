<?php

declare(strict_types=1);

namespace Expansa\Log\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a level name or number is not one of the PSR-3 levels or RFC 5424 codes.
 *
 * @package Expansa\Log\Exceptions
 */
final class InvalidLevel extends InvalidArgumentException {}
