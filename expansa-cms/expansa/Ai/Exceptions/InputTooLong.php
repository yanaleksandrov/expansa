<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a request exceeds the configured input token limit.
 */
final class InputTooLong extends InvalidArgumentException {}
