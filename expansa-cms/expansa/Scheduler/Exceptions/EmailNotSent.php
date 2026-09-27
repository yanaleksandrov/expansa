<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Exceptions;

use RuntimeException;

/**
 * Thrown when the configured mailer does not send the output of an email() job.
 *
 * @package Expansa\Scheduler
 */
final class EmailNotSent extends RuntimeException {}
