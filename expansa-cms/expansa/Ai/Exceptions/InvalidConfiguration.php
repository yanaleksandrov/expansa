<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when package configuration contains unsupported values.
 */
final class InvalidConfiguration extends InvalidArgumentException {}
