<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a CRON expression or one of its parts cannot be parsed.
 *
 * @package Expansa\Scheduler
 */
final class InvalidExpression extends InvalidArgumentException {}
