<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Exceptions;

use InvalidArgumentException;

/**
 * Thrown on an invalid CRON expression, job schedule or scheduler configuration.
 *
 * @package Expansa\Scheduler\Exception
 */
class SchedulerException extends InvalidArgumentException {}
