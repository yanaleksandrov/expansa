<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Exceptions;

use LogicException;

/**
 * Thrown when a job needs something Scheduler::configure() has not set, e.g. email() without a mailer.
 *
 * @package Expansa\Scheduler
 */
final class InvalidConfiguration extends LogicException {}
