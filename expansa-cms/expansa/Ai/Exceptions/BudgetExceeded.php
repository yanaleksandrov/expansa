<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use OverflowException;

/**
 * Thrown when the session has spent its total token budget before a required provider call.
 */
final class BudgetExceeded extends OverflowException {}
