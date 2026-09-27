<?php

declare(strict_types=1);

namespace Expansa\Hooks\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when configure() gets a listener class that does not exist or can not be created,
 * or a listener file without a namespace.
 *
 * @package Expansa\Hooks
 */
final class InvalidListener extends InvalidArgumentException {}
