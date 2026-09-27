<?php

declare(strict_types=1);

namespace Expansa\Log\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a channel is not configured, includes itself, has an unsupported driver
 * or misses a required option.
 *
 * @package Expansa\Log\Exceptions
 */
final class InvalidConfiguration extends InvalidArgumentException {}
