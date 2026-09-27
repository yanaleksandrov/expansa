<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when extension generation receives no user request.
 */
final class EmptyRequest extends InvalidArgumentException {}
