<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use RuntimeException;

/**
 * Thrown from the worker's progress callback when the task was cancelled; it stops the manager round.
 */
final class Cancelled extends RuntimeException {}
