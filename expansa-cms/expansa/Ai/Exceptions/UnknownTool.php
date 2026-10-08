<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when AI requests a tool that is not registered.
 */
final class UnknownTool extends InvalidArgumentException {}
