<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a value of a schedule method is out of range: `hourly(60)`, `daily('25:00')`.
 *
 * @package Expansa\Scheduler
 */
final class InvalidInterval extends InvalidArgumentException {}
