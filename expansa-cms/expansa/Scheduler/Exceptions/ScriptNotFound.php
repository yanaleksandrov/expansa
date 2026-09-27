<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Exceptions;

use RuntimeException;

/**
 * Thrown when the script of a php() job does not exist; the job is reported as failed, not queued.
 *
 * @package Expansa\Scheduler
 */
final class ScriptNotFound extends RuntimeException {}
