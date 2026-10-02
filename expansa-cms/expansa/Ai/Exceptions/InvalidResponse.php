<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use UnexpectedValueException;

/**
 * Thrown when an AI provider returns data outside the package protocol.
 */
final class InvalidResponse extends UnexpectedValueException {}
